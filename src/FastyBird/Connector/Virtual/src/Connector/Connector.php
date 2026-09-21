<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           18.10.23
 */

namespace FastyBird\Connector\Virtual\Connector;

use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ExchangeExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use React\EventLoop;
use React\Promise;
use TypeError;
use ValueError;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Devices\Devices|null $devices = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	/**
	 * @param array<Writers\WriterFactory> $writersFactories
	 */
	public function __construct(
		private readonly DevicesDocuments\Connectors\Connector $connector,
		private readonly Devices\DevicesFactory $devicesFactory,
		private readonly array $writersFactories,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Virtual\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		assert($this->connector instanceof Documents\Connectors\Connector);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		$this->logger->info(
			'Starting Virtual connector service',
			[
				'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->devices = $this->devicesFactory->create($this->connector);

		foreach ($this->writersFactories as $writerFactory) {
			if (
				(
					$standalone
					&& $writerFactory instanceof Writers\ExchangeFactory
				) || (
					!$standalone
					&& $writerFactory instanceof Writers\EventFactory
				)
			) {
				$this->writer = $writerFactory->create($this->connector);
				$this->writer->connect();
			}
		}

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->devices->start();

		$this->logger->info(
			'Virtual connector service has been started',
			[
				'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		return Promise\reject(
			new Exceptions\InvalidState('Devices discovery is not allowed for Virtual connector type'),
		);
	}

	public function terminate(): void
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		$this->devices?->stop();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Virtual connector has been terminated',
			[
				'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function hasUnfinishedTasks(): bool
	{
		return !$this->queue->isEmpty() && $this->consumersTimer !== null;
	}

}
