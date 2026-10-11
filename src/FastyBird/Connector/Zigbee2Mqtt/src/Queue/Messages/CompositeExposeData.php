<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function array_map;

/**
 * Expose data row
 */
final readonly class CompositeExposeData implements Message
{

	/**
	 * @param array<SingleExposeData|CompositeExposeData> $states
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeDataType::COMPOSITE->value])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: SingleExposeData::class),
				new ObjectMapper\Rules\MappedObjectValue(class: self::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $states,
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): Types\ExposeDataType
	{
		return Types\ExposeDataType::from($this->type);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
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
	#[Override]
	public function toArray(): array
	{
		return [
			'type' => $this->getType()->value,
			'identifier' => $this->getIdentifier(),
			'states' => array_map(
				static fn (SingleExposeData|self $item): array => $item->toArray(),
				$this->getStates(),
			),
		];
	}

}
