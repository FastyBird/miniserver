<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Override;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;
use function assert;
use function sprintf;

/**
 * NS panel third party device factory
 */
final readonly class ThirdPartyDeviceFactory implements DeviceFactory
{

	public function __construct(
		private Mapping\Builder $mappingBuilder,
		private Helpers\Connectors\Connector $connectorHelper,
		private Helpers\Devices\ThirdPartyDevice $deviceHelper,
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
	#[Override]
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

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\ThirdPartyDevice::class;
	}

}
