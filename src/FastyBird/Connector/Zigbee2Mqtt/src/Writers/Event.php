<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Writers;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Override;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Event based properties writer
 */
class Event extends Periodic implements Writer, EventDispatcher\EventSubscriberInterface
{

	public const string NAME = 'event';

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateChanged',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateChanged',
		];
	}

	public function stateChanged(
		DevicesEvents\ChannelPropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		try {
			$state = $event->getGet();

			if ($state->getExpectedValue() === null || $state->getPending() !== true) {
				return;
			}

			$findChannelQuery = new Queries\Configuration\FindChannels();
			$findChannelQuery->byId($event->getProperty()->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				Documents\Channels\Channel::class,
			);

			if ($channel === null) {
				return;
			}

			$findDeviceQuery = new Queries\Configuration\FindSubDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($channel->getDevice());

			$device = $this->devicesConfigurationRepository->findOneBy(
				$findDeviceQuery,
				Documents\Devices\SubDevice::class,
			);

			if ($device === null) {
				return;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\WriteSubDeviceChannelPropertyState::class,
					[
						'connector' => $this->connector->getId(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'property' => $event->getProperty()->getId(),
						'state' => $event->getGet()->toArray(),
					],
				),
			);

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'Characteristic value could not be prepared for writing',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'event-writer',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

}
