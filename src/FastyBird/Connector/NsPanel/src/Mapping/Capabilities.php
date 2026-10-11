<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Mapping;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use function sprintf;

/**
 * Capabilities mapping configuration
 */
readonly class Capabilities implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Capabilities\Group> $groups
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Capabilities\Group::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $groups,
	)
	{
	}

	/**
	 * @return array<Mapping\Capabilities\Group>
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getGroup(Types\Group $type): Mapping\Capabilities\Group
	{
		foreach ($this->groups as $group) {
			if ($group->getType() === $type) {
				return $group;
			}
		}

		throw new Exceptions\InvalidState(sprintf('Group type: %s is not configured', $type->value));
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findByCapabilityName(
		Types\Capability $type,
		string|null $name = null,
	): Mapping\Capabilities\Capability|null
	{
		foreach ($this->groups as $group) {
			foreach ($group->getCapabilities() as $capability) {
				if ($capability->getCapability() === $type) {
					if ($capability->getName() === null || $capability->getName() === $name) {
						return $capability;
					}
				}
			}
		}

		return null;
	}

}
