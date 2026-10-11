<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Bridge group description message
 */
final class StoreBridgeGroups extends Bridge implements Message
{

	/**
	 * @param array<GroupDescription> $groups
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: GroupDescription::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $groups,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	/**
	 * @return array<GroupDescription>
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'group' => array_map(
					static fn (GroupDescription $group): array => $group->toArray(),
					$this->getGroups(),
				),
			],
		);
	}

}
