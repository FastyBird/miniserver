<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           20.01.24
 */

namespace FastyBird\Module\Devices\Subscribers;

use Doctrine\DBAL;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Types;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * Devices state entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsEntitiesRepository,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Utilities\DeviceConnection $deviceConnectionManager,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\BeforeConnectorExecutionStart::class => 'executionStarting',
			Events\AfterConnectorExecutionStart::class => 'executionStarted',

			Events\AfterConnectorExecutionTerminate::class => 'executionTerminated',
		];
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function executionStarting(Events\BeforeConnectorExecutionStart $event): void
	{
		$this->resetConnector(
			$event->getConnector(),
			Types\ConnectionState::UNKNOWN,
		);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function executionStarted(Events\AfterConnectorExecutionStart $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			Types\ConnectionState::RUNNING,
			$this->getSource($event->getConnector()),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function executionTerminated(Events\AfterConnectorExecutionTerminate $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			Types\ConnectionState::STOPPED,
			$this->getSource($event->getConnector()),
		);

		$this->resetConnector(
			$event->getConnector(),
			Types\ConnectionState::DISCONNECTED,
		);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetConnector(
		Documents\Connectors\Connector $connector,
		Types\ConnectionState $state,
	): void
	{
		$source = $this->getSource($connector);

		$findConnectorPropertiesQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertiesQuery->forConnector($connector);

		$properties = $this->connectorsPropertiesConfigurationRepository->findAllBy(
			$findConnectorPropertiesQuery,
			Documents\Connectors\Properties\Dynamic::class,
		);

		foreach ($properties as $property) {
			$this->connectorPropertiesStatesManager->setValidState(
				$property,
				false,
				$source,
			);
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		foreach ($devices as $device) {
			$this->resetDevice($device, $state, $source);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetDevice(
		Documents\Devices\Device $device,
		Types\ConnectionState $state,
		Sources\Source $source,
	): void
	{
		$this->deviceConnectionManager->setState($device, $state, $source);

		$findDevicePropertiesQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertiesQuery->forDevice($device);

		$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
			$findDevicePropertiesQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		foreach ($properties as $property) {
			$this->devicePropertiesStatesManager->setValidState(
				$property,
				false,
				$source,
			);
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$this->resetChanel($channel, $source);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetChanel(Documents\Channels\Channel $channel, Sources\Source $source): void
	{
		$findChannelPropertiesQuery = new Queries\Configuration\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
			$findChannelPropertiesQuery,
			Documents\Channels\Properties\Dynamic::class,
		);

		foreach ($properties as $property) {
			$this->channelPropertiesStatesManager->setValidState(
				$property,
				false,
				$source,
			);
		}
	}

	/**
	 * The states this subscriber writes are the connector's own reports (its connection state, and
	 * its properties no longer being valid once it stops), so they carry the connector's source.
	 * A document with the Devices source is a command, and WebSocket clients do not receive it. The
	 * connector documents carry no source of their own (their getSource() is Devices, and is part
	 * of what they serialize to), the connector entities do.
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getSource(Documents\Connectors\Connector $connector): Sources\Source
	{
		$entity = $this->connectorsEntitiesRepository->find($connector->getId());

		if ($entity === null) {
			throw new DevicesExceptions\InvalidState('Connector could not be loaded to report its state');
		}

		return $entity->getSource();
	}

}
