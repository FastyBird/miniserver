<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Connector;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use React\EventLoop;
use React\Promise;
use ReflectionClass;
use RuntimeException;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Clients\Client|Clients\Discovery|null $client = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 * @param array<Writers\WriterFactory> $writersFactories
	 */
	public function __construct(
		private readonly DevicesDocuments\Connectors\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Clients\DiscoveryFactory $discoveryClientFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly array $writersFactories,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Shelly\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		assert($this->connector instanceof Documents\Connectors\Connector);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws ShellyExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		$this->logger->info(
			'Starting Shelly connector service',
			[
				'source' => Sources\Connector::SHELLY->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$mode = $this->connectorHelper->getClientMode($this->connector);

		foreach ($this->clientsFactories as $clientFactory) {
			$rc = new ReflectionClass($clientFactory);

			$constants = $rc->getConstants();

			if (
				array_key_exists(Clients\ClientFactory::MODE_CONSTANT_NAME, $constants)
				&& $mode === $constants[Clients\ClientFactory::MODE_CONSTANT_NAME]
			) {
				$this->client = $clientFactory->create($this->connector);
			}
		}

		if (
			!$this->client instanceof Clients\Local
			&& !$this->client instanceof Clients\Cloud
			&& !$this->client instanceof Clients\Mqtt
		) {
			return Promise\reject(new ShellyExceptions\InvalidState('Connector client is not configured'));
		}

		$this->client->connect();

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

		$this->logger->info(
			'Shelly connector service has been started',
			[
				'source' => Sources\Connector::SHELLY->value,
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
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		$this->logger->info(
			'Starting Shelly connector discovery',
			[
				'source' => Sources\Connector::SHELLY->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->client = $this->discoveryClientFactory->create($this->connector);

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Shelly connector discovery has been started',
			[
				'source' => Sources\Connector::SHELLY->value,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->client->discover();

		return Promise\resolve(true);
	}

	public function terminate(): void
	{
		assert($this->connector instanceof Documents\Connectors\Connector);

		$this->client?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Shelly connector has been terminated',
			[
				'source' => Sources\Connector::SHELLY->value,
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
