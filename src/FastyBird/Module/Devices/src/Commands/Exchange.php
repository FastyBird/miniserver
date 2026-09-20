<?php declare(strict_types = 1);

/**
 * Exchange.php
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

use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Messaging\Exchange as ExchangeExchange;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Consumers;
use FastyBird\Module\Devices\Events;
use Nette;
use Nette\Localization;
use Psr\EventDispatcher;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use const SIGINT;
use const SIGTERM;

/**
 * Module exchange command
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Exchange extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:devices-module:exchange';

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Devices\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly Localization\Translator $translator,
		private readonly array $exchangeFactories = [],
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
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
			->setDescription('Devices module exchange');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title((string) $this->translator->translate('//devices-module.cmd.exchange.title'));

			$io->note((string) $this->translator->translate('//devices-module.cmd.exchange.subtitle'));
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

		try {
			$this->logger->info(
				'Starting devices exchange...',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'exchange-cmd',
				],
			);

			$this->dispatcher?->dispatch(new Events\ExchangeStartup());

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}

			$this->consumer->enable(Consumers\StatesActions::class);

			$this->eventLoop->addSignal(SIGTERM, function (): void {
				$this->terminate();
			});

			$this->eventLoop->addSignal(SIGINT, function (): void {
				$this->terminate();
			});

			$this->eventLoop->run();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'exchange-cmd',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			if ($input->getOption('quiet') === false) {
				$io->error((string) $this->translator->translate('//devices-module.cmd.exchange.messages.error'));
			}

			return Console\Command\Command::FAILURE;
		}

		return self::SUCCESS;
	}

	private function terminate(): void
	{
		$this->logger->info(
			'Stopping devices exchange...',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'exchange-cmd',
			],
		);

		$this->eventLoop->stop();
	}

}
