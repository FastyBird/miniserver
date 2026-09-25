<?php declare(strict_types = 1);

/**
 * ThirdPartyDeviceFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;
use function assert;
use function sprintf;

/**
 * NS panel third party device factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThirdPartyDeviceFactory implements DeviceFactory
{

	public function __construct(
		private readonly Mapping\Builder $mappingBuilder,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\ThirdPartyDevice $deviceHelper,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidState
	 * @throws NsPanelExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function create(
		Documents\Connectors\Connector $connector,
		Documents\Devices\Gateway $gateway,
		Documents\Devices\Device $device,
		Mapping\Categories\Category $categoryMetadata,
	): ThirdPartyDevice
	{
		assert($device instanceof Documents\Devices\ThirdPartyDevice);

		return new ThirdPartyDevice(
			$device->getId(),
			$device->getIdentifier(),
			$this->deviceHelper->getGatewayIdentifier($device),
			$gateway->getId(),
			$connector->getId(),
			$this->deviceHelper->getDisplayCategory($device),
			$device->getName() ?? $device->getIdentifier(),
			$this->deviceHelper->getManufacturer($device),
			$this->deviceHelper->getModel($device),
			$this->deviceHelper->getFirmwareVersion($device),
			sprintf(
				'http://%s:%d/do-directive/%s/%s',
				Helpers\Network::getLocalAddress(),
				$this->connectorHelper->getPort($connector),
				$gateway->getId()->toString(),
				$device->getId()->toString(),
			),
			false,
			array_merge(
				...array_map(function (Types\Group $type): array {
					$group = $this->mappingBuilder->getCapabilitiesMapping()->getGroup($type);

					return array_map(
						static fn (Mapping\Capabilities\Capability $capabilityMeta): Types\Capability => $capabilityMeta->getCapability(),
						$group->getCapabilities(),
					);
				}, $categoryMetadata->getRequiredCapabilitiesGroups()),
			),
			array_merge(
				...array_map(function (Types\Group $type): array {
					$group = $this->mappingBuilder->getCapabilitiesMapping()->getGroup($type);

					return array_map(
						static fn (Mapping\Capabilities\Capability $capabilityMeta): Types\Capability => $capabilityMeta->getCapability(),
						$group->getCapabilities(),
					);
				}, $categoryMetadata->getOptionalCapabilitiesGroups()),
			),
		);
	}

	public function getEntityClass(): string
	{
		return Entities\Devices\ThirdPartyDevice::class;
	}

}
