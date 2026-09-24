<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Writers;

use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ExchangeExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Messaging\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use React\EventLoop;
use Throwable;
use function array_merge;
use function str_starts_with;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, ExchangeConsumers\Consumer
{

	public const NAME = 'exchange';

	public function __construct(
		Documents\Connectors\Connector $connector,
		Helpers\MessageBuilder $messageBuilder,
		Queue\Queue $queue,
		Virtual\Logger $logger,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		Clock\Clock $clock,
		EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$messageBuilder,
			$queue,
			$logger,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$devicesPropertiesConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$devicePropertiesStatesManager,
			$channelPropertiesStatesManager,
			$clock,
			$eventLoop,
		);

		$this->consumer->register($this, null, false);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		parent::connect();

		$this->consumer->enable(self::class);
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function disconnect(): void
	{
		parent::disconnect();

		$this->consumer->disable(self::class);
	}

	public function consume(
		Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void
	{
		try {
			if ($document instanceof DevicesDocuments\States\Devices\Properties\Property) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($document->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					Documents\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
				$findPropertyQuery->byId($document->getId());
				$findPropertyQuery->forDevice($device);

				$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

				if ($property === null) {
					return;
				}

				if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteDevicePropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'property' => $document->getId(),
								'state' => array_merge(
									$document->getRead()->toArray(),
									[
										'id' => $document->getId(),
										'valid' => $document->isValid(),
										'pending' => $document->getPending(),
									],
								),
							],
						),
					);
				} elseif ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteDevicePropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'property' => $document->getId(),
								'state' => array_merge(
									$document->getGet()->toArray(),
									[
										'id' => $document->getId(),
										'valid' => $document->isValid(),
										'pending' => $document->getPending(),
									],
								),
							],
						),
					);
				}
			} elseif ($document instanceof DevicesDocuments\States\Channels\Properties\Property) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($document->getChannel());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					Documents\Channels\Channel::class,
				);

				if ($channel === null) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($channel->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					Documents\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				$findPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
				$findPropertyQuery->byId($document->getId());
				$findPropertyQuery->forChannel($channel);

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findPropertyQuery);

				if ($property === null) {
					return;
				}

				if ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteChannelPropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'channel' => $channel->getId(),
								'property' => $document->getId(),
								'state' => array_merge(
									$document->getRead()->toArray(),
									[
										'id' => $document->getId(),
										'valid' => $document->isValid(),
										'pending' => $document->getPending(),
									],
								),
							],
						),
					);
				} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteChannelPropertyState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'channel' => $channel->getId(),
								'property' => $document->getId(),
								'state' => array_merge(
									$document->getGet()->toArray(),
									[
										'id' => $document->getId(),
										'valid' => $document->isValid(),
										'pending' => $document->getPending(),
									],
								),
							],
						),
					);
				}
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'Characteristic value could not be prepared for writing',
				[
					'source' => Sources\Connector::VIRTUAL->value,
					'type' => 'exchange-writer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

}
