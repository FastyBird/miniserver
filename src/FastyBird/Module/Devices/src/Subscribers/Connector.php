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
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function executionStarted(Events\AfterConnectorExecutionStart $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			Types\ConnectionState::RUNNING,
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function executionTerminated(Events\AfterConnectorExecutionTerminate $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			Types\ConnectionState::STOPPED,
		);

		$this->resetConnector(
			$event->getConnector(),
			Types\ConnectionState::DISCONNECTED,
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetConnector(
		Documents\Connectors\Connector $connector,
		Types\ConnectionState $state,
	): void
	{
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
				MetadataTypes\Sources\Module::DEVICES,
			);
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		foreach ($devices as $device) {
			$this->resetDevice($device, $state);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetDevice(
		Documents\Devices\Device $device,
		Types\ConnectionState $state,
	): void
	{
		$this->deviceConnectionManager->setState($device, $state);

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
				MetadataTypes\Sources\Module::DEVICES,
			);
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$this->resetChanel($channel);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function resetChanel(Documents\Channels\Channel $channel): void
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
				MetadataTypes\Sources\Module::DEVICES,
			);
		}
	}

}
