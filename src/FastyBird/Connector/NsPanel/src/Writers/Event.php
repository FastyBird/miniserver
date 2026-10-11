<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Writers;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Queries as NsPanelQueries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Event based properties writer
 */
class Event extends Periodic implements Writer, EventDispatcher\EventSubscriberInterface
{

	public const string NAME = 'event';

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
	}

	public function connect(): void
	{
		// Nothing to do here
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	public function stateChanged(
		DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		try {
			$findChannelQuery = new NsPanelQueries\Configuration\FindChannels();
			$findChannelQuery->byId($event->getProperty()->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				Documents\Channels\Channel::class,
			);

			if ($channel === null) {
				return;
			}

			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($channel->getDevice());

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			if ($device instanceof Documents\Devices\SubDevice) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteSubDeviceState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'channel' => $channel->getId(),
							'property' => $event->getProperty()->getId(),
							'state' => $event->getGet()->toArray(),
						],
					),
				);

			} elseif ($device instanceof Documents\Devices\ThirdPartyDevice) {
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
							'property' => $event->getProperty()->getId(),
							'state' => $event->getGet()->toArray(),
						],
					),
				);
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'Characteristic value could not be prepared for writing',
				[
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'event-writer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

}
