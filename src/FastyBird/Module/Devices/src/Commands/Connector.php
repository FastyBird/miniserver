<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Module\Devices\Commands;

use BadMethodCallException;
use DateTimeInterface;
use Doctrine\DBAL;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Core\Exchange\Exchange as ExchangeExchange;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Consumers;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Types;
use Nette\Localization;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use Ramsey\Uuid;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function intval;
use function is_string;
use function React\Async\async;
use function React\Async\await;
use function sprintf;
use function usort;
use const SIGINT;
use const SIGTERM;

/**
 * Module connector command
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Connector extends Console\Command\Command
{

	public const NAME = 'fb:devices-module:connector';

	private const SHUTDOWN_WAITING_DELAY = 3;

	private const DATABASE_REFRESH_INTERVAL = 5;

	private const DISCOVERY_MAX_PROCESSING_INTERVAL = 120.0;

	private bool $isTerminating = false;

	private Console\Helper\ProgressBar|null $progressBar = null;

	private EventLoop\TimerInterface|null $databaseRefreshTimer = null;

	private EventLoop\TimerInterface|null $progressBarTimer = null;

	private EventLoop\TimerInterface|null $discoveryTimer = null;

	private DateTimeInterface|null $executedAt = null;

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Connectors\ContainerFactory $serviceFactory,
		private readonly Models\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly Models\Configuration\Connectors\Controls\Repository $connectorsControlsConfigurationRepository,
		private readonly Devices\Logger $logger,
		private readonly ToolsHelpers\Database $database,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly DateTimeFactory\Clock $clock,
		private readonly Localization\Translator $translator,
		private readonly array $exchangeFactories = [],
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Devices module connector')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_REQUIRED,
						'Connector ID or identifier',
					),
					new Input\InputOption(
						'mode',
						'm',
						Input\InputOption::VALUE_OPTIONAL,
						'Connector mode',
						Types\ConnectorMode::EXECUTE->value,
					),
				]),
			);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title((string) $this->translator->translate('//devices-module.cmd.connector.title'));

			$io->note((string) $this->translator->translate('//devices-module.cmd.connector.subtitle'));
		}

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//devices-module.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$mode = Types\ConnectorMode::EXECUTE;

		if (
			$input->hasOption('mode')
			&& is_string($input->getOption('mode'))
			&& Types\ConnectorMode::tryFrom(Utils\Strings::lower($input->getOption('mode'))) !== null
		) {
			$mode = Types\ConnectorMode::from(Utils\Strings::lower($input->getOption('mode')));
		}

		if ($mode === Types\ConnectorMode::DISCOVER) {
			$this->progressBar = new Console\Helper\ProgressBar(
				$output,
				intval(self::DISCOVERY_MAX_PROCESSING_INTERVAL),
			);

			$this->progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
		}

		try {
			$this->isTerminating = false;

			if ($this->prepare($io, $input, $mode) === Console\Command\Command::FAILURE) {
				return Console\Command\Command::FAILURE;
			}

			$this->executedAt = $this->clock->getNow();

			$this->eventLoop->run();

			$this->progressBar?->finish();

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			if ($input->getOption('quiet') === false) {
				$io->error((string) $this->translator->translate('//devices-module.cmd.connector.messages.error'));
			}

			$this->eventLoop->stop();

			return Console\Command\Command::FAILURE;
		}
	}

	/**
	 * @throws BadMethodCallException
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function prepare(
		Style\SymfonyStyle $io,
		Input\InputInterface $input,
		Types\ConnectorMode $mode,
	): int
	{
		if ($input->getOption('quiet') === false) {
			$io->section((string) $this->translator->translate('//devices-module.cmd.connector.info.preparing'));
		}

		$connector = $this->whichConnector($io, $input, $mode);

		if ($connector === null) {
			if ($input->getOption('quiet') === false) {
				if ($mode === Types\ConnectorMode::DISCOVER) {
					$io->warning(
						(string) $this->translator->translate(
							'//devices-module.cmd.connector.messages.noDiscoverableConnectors',
						),
					);
				} else {
					$io->warning(
						(string) $this->translator->translate(
							'//devices-module.cmd.connector.messages.noConnectors',
						),
					);
				}
			}

			return Console\Command\Command::SUCCESS;
		} elseif ($connector === false) {
			return Console\Command\Command::FAILURE;
		}

		if ($input->getOption('quiet') === false) {
			$io->section((string) $this->translator->translate('//devices-module.cmd.connector.info.initialization'));
		}

		$this->dispatcher?->dispatch(new Events\ConnectorStartup($connector));

		$this->consumer->enable(Consumers\ModuleEntities::class);

		$service = $this->serviceFactory->create($connector);

		$service->onTerminate[] = function (
			MetadataTypes\Sources\Source $source,
			string|null $reason,
			Throwable|null $ex,
		) use (
			$service,
			$mode,
			$connector,
		): void {
			if ($ex !== null) {
				$this->logger->warning(
					'Triggering connector termination due to some error',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-cmd',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'reason' => [
							'source' => $source->value,
							'message' => $reason,
						],
					],
				);
			} else {
				$this->logger->info(
					'Triggering connector termination',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-cmd',
						'reason' => [
							'source' => $source->value,
							'message' => $reason,
						],
					],
				);
			}

			$this->terminate($connector, $service, $mode);
		};

		$service->onRestart[] = function (
			MetadataTypes\Sources\Source $source,
			string|null $reason,
			Throwable|null $ex,
		) use (
			$service,
			$mode,
			$connector,
		): void {
			if ($ex !== null) {
				$this->logger->warning(
					'Triggering connector restart due to some error',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-cmd',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'reason' => [
							'source' => $source->value,
							'message' => $reason,
						],
					],
				);
			} else {
				$this->logger->info(
					'Triggering connector restart',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-cmd',
						'reason' => [
							'source' => $source->value,
							'message' => $reason,
						],
					],
				);
			}

			$this->terminate($connector, $service, $mode);
		};

		if ($mode === Types\ConnectorMode::DISCOVER) {
			$this->progressBarTimer = $this->eventLoop->addPeriodicTimer(
				0.1,
				async(function (): void {
					if ($this->executedAt !== null) {
						$this->progressBar?->setProgress(
							$this->clock->getNow()->getTimestamp() - $this->executedAt->getTimestamp(),
						);
					} else {
						$this->progressBar?->advance();
					}
				}),
			);
		}

		$this->eventLoop->futureTick(async(function () use ($connector, $service, $mode): void {
			if ($mode === Types\ConnectorMode::DISCOVER) {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorDiscoveryStart($connector));

				$this->logger->debug('Starting connector...', [
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-cmd',
				]);

				$this->eventLoop->futureTick(async(function () use ($connector, $service, $mode): void {
					try {
						// Start connector service
						await($service->discover());

						$this->dispatcher?->dispatch(new Events\AfterConnectorDiscoveryStart($connector));
					} catch (Throwable $ex) {
						$this->logger->error(
							'Connector discovery can\'t be started',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'connector-cmd',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);

						$this->terminate($connector, $service, $mode);
					}
				}));

			} else {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorExecutionStart($connector));

				$this->logger->debug('Starting connector...', [
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-cmd',
				]);

				$this->eventLoop->futureTick(async(function () use ($connector, $service, $mode): void {
					try {
						// Start connector service
						await($service->execute());

						$this->dispatcher?->dispatch(new Events\AfterConnectorExecutionStart($connector));
					} catch (Throwable $ex) {
						$this->logger->error(
							'Connector execution can\'t be started',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'connector-cmd',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);

						$this->terminate($connector, $service, $mode);
					}
				}));
			}

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}
		}));

		$this->eventLoop->addSignal(SIGTERM, function () use ($connector, $service, $mode): void {
			$this->terminate($connector, $service, $mode);
		});

		$this->eventLoop->addSignal(SIGINT, function () use ($connector, $service, $mode): void {
			$this->terminate($connector, $service, $mode);
		});

		if ($mode === Types\ConnectorMode::DISCOVER) {
			$this->discoveryTimer = $this->eventLoop->addTimer(
				self::DISCOVERY_MAX_PROCESSING_INTERVAL,
				function () use ($connector, $service, $mode): void {
					$this->terminate($connector, $service, $mode);
				},
			);
		}

		$this->databaseRefreshTimer = $this->eventLoop->addPeriodicTimer(
			self::DATABASE_REFRESH_INTERVAL,
			async(function (): void {
				$this->eventLoop->futureTick(async(function (): void {
					// Check if ping to DB is possible...
					if (!$this->database->ping()) {
						// ...if not, try to reconnect
						$this->database->reconnect();

						// ...and ping again
						if (!$this->database->ping()) {
							throw new Exceptions\Runtime('Connection to database could not be re-established');
						}
					}
				}));
			}),
		);

		$this->progressBar?->start();

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function terminate(
		Documents\Connectors\Connector $connector,
		Connectors\Connector $service,
		Types\ConnectorMode $mode,
	): void
	{
		if ($this->isTerminating) {
			return;
		}

		$this->isTerminating = true;

		$this->logger->debug(
			'Stopping connector...',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'connector-cmd',
			],
		);

		if ($this->discoveryTimer !== null) {
			$this->eventLoop->cancelTimer($this->discoveryTimer);
		}

		try {
			$this->dispatcher?->dispatch(new Events\BeforeConnectorTerminate($service));

			$service->terminate();

			// Wait until connector is fully terminated
			$this->eventLoop->addTimer(
				self::SHUTDOWN_WAITING_DELAY,
				async(function () use ($connector, $service, $mode): void {
					if ($mode === Types\ConnectorMode::DISCOVER) {
						$this->dispatcher?->dispatch(new Events\AfterConnectorDiscoveryTerminate($service, $connector));
					} else {
						$this->dispatcher?->dispatch(new Events\AfterConnectorExecutionTerminate($service, $connector));
					}

					if ($this->databaseRefreshTimer !== null) {
						$this->eventLoop->cancelTimer($this->databaseRefreshTimer);
					}

					if ($this->progressBarTimer !== null) {
						$this->eventLoop->cancelTimer($this->progressBarTimer);
					}

					$this->eventLoop->stop();
				}),
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Connector could not be stopped. An unexpected error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new Exceptions\Runtime(
				'Error during connector termination process',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function whichConnector(
		Style\SymfonyStyle $io,
		Input\InputInterface $input,
		Types\ConnectorMode $mode,
	): Documents\Connectors\Connector|false|null
	{
		if (
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new Queries\Configuration\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

			if ($connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->warning(
						(string) $this->translator->translate(
							'//devices-module.cmd.connector.messages.connectorNotFound',
						),
					);
				}

				return false;
			}

			if ($mode === Types\ConnectorMode::DISCOVER) {
				$findConnectorControlQuery = new Queries\Configuration\FindConnectorControls();
				$findConnectorControlQuery->forConnector($connector);
				$findConnectorControlQuery->byName(Types\ControlName::DISCOVER->value);

				$control = $this->connectorsControlsConfigurationRepository->findOneBy($findConnectorControlQuery);

				if ($control === null) {
					if ($input->getOption('quiet') === false) {
						$io->warning(
							(string) $this->translator->translate(
								'//devices-module.cmd.connector.messages.notSupportedConnector',
							),
						);
					}

					return false;
				}
			}
		} else {
			$systemConnectors = [];

			$findConnectorsQuery = new Queries\Configuration\FindConnectors();

			foreach ($this->connectorsConfigurationRepository->findAllBy($findConnectorsQuery) as $connector) {
				if ($mode === Types\ConnectorMode::DISCOVER) {
					$findConnectorControlQuery = new Queries\Configuration\FindConnectorControls();
					$findConnectorControlQuery->forConnector($connector);
					$findConnectorControlQuery->byName(Types\ControlName::DISCOVER->value);

					$control = $this->connectorsControlsConfigurationRepository->findOneBy($findConnectorControlQuery);

					if ($control === null) {
						continue;
					}
				}

				$systemConnectors[] = $connector;
			}

			if (count($systemConnectors) === 0) {
				return null;
			}

			usort(
				$systemConnectors,
				static fn (Documents\Connectors\Connector $a, Documents\Connectors\Connector $b): int => (
					($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
				),
			);

			$connectors = [];

			foreach ($systemConnectors as $connector) {
				$connectors[$connector->getIdentifier()] = $connector->getName() ?? $connector->getIdentifier();
			}

			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//devices-module.cmd.connector.questions.selectConnector'),
				array_values($connectors),
				count($connectors) === 1 ? 0 : null,
			);

			$question->setErrorMessage(
				(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(
				function (string|int|null $answer) use ($connectors): Documents\Connectors\Connector {
					if ($answer === null) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//devices-module.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					if (array_key_exists($answer, array_values($connectors))) {
						$answer = array_values($connectors)[$answer];
					}

					$identifier = array_search($answer, $connectors, true);

					if ($identifier !== false) {
						$findConnectorQuery = new Queries\Configuration\FindConnectors();
						$findConnectorQuery->byIdentifier($identifier);

						$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

						if ($connector !== null) {
							return $connector;
						}
					}

					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				},
			);

			$connector = $io->askQuestion($question);
			assert($connector instanceof Documents\Connectors\Connector);
		}

		if (!$connector->isEnabled()) {
			if ($input->getOption('quiet') === false) {
				$io->warning(
					(string) $this->translator->translate('//devices-module.cmd.connector.messages.connectorDisabled'),
				);
			}

			return false;
		}

		return $connector;
	}

}
