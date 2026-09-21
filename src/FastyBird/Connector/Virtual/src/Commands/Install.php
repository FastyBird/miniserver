<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           17.12.23
 */

namespace FastyBird\Connector\Virtual\Commands;

use Doctrine\DBAL;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\Localization;
use Nette\Utils;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:virtual-connector:install';

	private Input\InputInterface|null $input = null;

	private Output\OutputInterface|null $output = null;

	public function __construct(
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
		private readonly Localization\Translator $translator,
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
			->setDescription('Virtual connector installer');
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$this->input = $input;
		$this->output = $output;

		$io = new Style\SymfonyStyle($this->input, $this->output);

		$io->title((string) $this->translator->translate('//virtual-connector.cmd.install.title'));

		$io->note((string) $this->translator->translate('//virtual-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-connector.cmd.install.questions.provide.connector.identifier',
			),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						(string) $this->translator->translate(
							'//virtual-connector.cmd.install.messages.identifier.connector.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'virtual-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.identifier.connector.missing',
				),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Connectors\Connector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'install-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.create.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning((string) $this->translator->translate('//virtual-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-connector.cmd.install.questions.create.connector'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConnector($io);
			}

			return;
		}

		$name = $this->askConnectorName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-connector.cmd.install.questions.disable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'install-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.update.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$connector = $this->connectorsRepository->find(
			$connector->getId(),
			Entities\Connectors\Connector::class,
		);
		assert($connector instanceof Entities\Connectors\Connector);

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//virtual-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'install-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.remove.connector.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//virtual-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listConnectors(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$connectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//virtual-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//virtual-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteDevice(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning((string) $this->translator->translate('//virtual-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'devices-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//virtual-connector.cmd.install.messages.remove.device.error'),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);
		usort(
			$devices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//virtual-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//virtual-connector.cmd.install.data.model'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device::getType(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.create.connector'),
				1 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.update.connector'),
				2 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.remove.connector'),
				3 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.manage.connector'),
				4 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.list.connectors'),
				5 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.list.connectors',
			)
			|| $whatToDo === '4'
		) {
			$this->listConnectors($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.remove.device'),
				1 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.list.devices'),
				2 => (string) $this->translator->translate('//virtual-connector.cmd.install.actions.nothing'),
			],
			2,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '0'
		) {
			$this->deleteDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-connector.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '1'
		) {
			$this->listDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate('//virtual-connector.cmd.install.questions.provide.connector.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\Connectors\Connector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getName() ?? $connector->getIdentifier();
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\Connectors\Connector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\Connectors\Connector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): Entities\Devices\Device|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\Device::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getName() ?? $device->getIdentifier();
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\Devices\Device {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Entities\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\Device::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Device);

		return $device;
	}

}
