<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\Modbus\Writers;

use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Documents as ModbusDocuments;
use FastyBird\Connector\Modbus\Exceptions as ModbusExceptions;
use FastyBird\Connector\Modbus\Helpers\MessageBuilder;
use FastyBird\Connector\Modbus\Queries;
use FastyBird\Connector\Modbus\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ExchangeExceptions;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use React\EventLoop;
use Throwable;
use function array_merge;
use function str_starts_with;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, Consumers\Consumer
{

	public const NAME = 'exchange';

	public function __construct(
		ModbusDocuments\Connectors\Connector $connector,
		MessageBuilder $messageBuilder,
		Queue\Queue $queue,
		Modbus\Logger $logger,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		Clock\Clock $clock,
		EventLoop\LoopInterface $eventLoop,
		private readonly Consumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$messageBuilder,
			$queue,
			$logger,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$channelPropertiesStatesManager,
			$clock,
			$eventLoop,
		);

		$this->consumer->register($this, null, false);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ModbusExceptions\Runtime
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
		CoreDocuments\Document|null $document,
	): void
	{
		try {
			if ($document instanceof DevicesDocuments\States\Channels\Properties\Property) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				if (
					$document->getGet()->getExpectedValue() === null
					|| $document->getPending() !== true
				) {
					return;
				}

				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($document->getChannel());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					ModbusDocuments\Channels\Channel::class,
				);

				if ($channel === null) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($channel->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					ModbusDocuments\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteChannelPropertyState::class,
						[
							'connector' => $this->connector->getId(),
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
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'Characteristic value could not be prepared for writing',
				[
					'source' => Sources\Connector::MODBUS->value,
					'type' => 'exchange-writer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

}
