<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;

/**
 * Device state description message
 */
final class StoreDeviceState extends Device implements Message
{

	/**
	 * @param array<SingleExposeData|CompositeExposeData> $states
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		string $device,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: SingleExposeData::class),
				new ObjectMapper\Rules\MappedObjectValue(class: CompositeExposeData::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $states,
	)
	{
		parent::__construct($connector, $baseTopic, $device);
	}

	/**
	 * @return array<SingleExposeData|CompositeExposeData>
	 */
	public function getStates(): array
	{
		return $this->states;
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'states' => array_map(
					static fn (SingleExposeData|CompositeExposeData $item): array => $item->toArray(),
					$this->getStates(),
				),
			],
		);
	}

}
