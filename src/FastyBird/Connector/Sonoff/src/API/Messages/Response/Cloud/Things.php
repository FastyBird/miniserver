<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User things list for home entity
 */
final readonly class Things implements API\Messages\Message
{

	/**
	 * @param array<Device> $devices
	 * @param array<Group> $groups
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Device::class),
		)]
		private array $devices,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Group::class),
		)]
		private array $groups,
	)
	{
	}

	/**
	 * @return array<Device>
	 */
	public function getDevices(): array
	{
		return $this->devices;
	}

	/**
	 * @return array<Group>
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'devices' => array_map(static fn (Device $device): array => $device->toArray(), $this->getDevices()),
			'groups' => array_map(static fn (Group $group): array => $group->toArray(), $this->getGroups()),
		];
	}

}
