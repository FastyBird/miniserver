<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Writers;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents as NsPanelDocuments;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
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
use FastyBird\Module\Devices\Types as DevicesTypes;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
use function str_starts_with;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, Consumers\Consumer
{

	public const NAME = 'exchange';

	public function __construct(
		NsPanelDocuments\Connectors\Connector $connector,
		Helpers\MessageBuilder $messageBuilder,
		Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		Queue\Queue $queue,
		NsPanel\Logger $logger,
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
			$thirdPartyDeviceHelper,
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
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
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

				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($document->getChannel());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					NsPanelDocuments\Channels\Channel::class,
				);

				if ($channel === null) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($channel->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					NsPanelDocuments\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				if ($device instanceof NsPanelDocuments\Devices\SubDevice) {
					if (
						$document->getGet()->getExpectedValue() === null
						|| $document->getPending() !== true
					) {
						return;
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteSubDeviceState::class,
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

				} elseif ($device instanceof NsPanelDocuments\Devices\ThirdPartyDevice) {
					if ($this->thirdPartyDeviceHelper->getGatewayIdentifier($device) === null) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);

						return;
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteThirdPartyDeviceState::class,
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
			} elseif ($document instanceof DevicesDocuments\Channels\Properties\Variable) {
				if (str_starts_with($routingKey, DevicesConstants::MESSAGE_BUS_DELETED_ROUTING_KEY)) {
					return;
				}

				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId($document->getChannel());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					NsPanelDocuments\Channels\Channel::class,
				);

				if ($channel === null) {
					return;
				}

				$findDeviceQuery = new Queries\Configuration\FindDevices();
				$findDeviceQuery->forConnector($this->connector);
				$findDeviceQuery->byId($channel->getDevice());

				$device = $this->devicesConfigurationRepository->findOneBy(
					$findDeviceQuery,
					NsPanelDocuments\Devices\Device::class,
				);

				if ($device === null) {
					return;
				}

				if ($device instanceof NsPanelDocuments\Devices\SubDevice) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteSubDeviceState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'channel' => $channel->getId(),
								'property' => $document->getId(),
							],
						),
					);

				} elseif ($device instanceof NsPanelDocuments\Devices\ThirdPartyDevice) {
					if ($this->thirdPartyDeviceHelper->getGatewayIdentifier($device) === null) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);

						return;
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\WriteThirdPartyDeviceState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'channel' => $channel->getId(),
								'property' => $document->getId(),
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
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'exchange-writer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

}
