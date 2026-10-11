<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function array_merge;

/**
 * List type expose type message
 */
final class ListType extends Type
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeType::LIGHT->value])]
		private readonly string $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('length_min')]
		private readonly int|null $lengthMin,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('length_max')]
		private readonly int|null $length_max,
	)
	{
		parent::__construct($name, $label, $property, $access);
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function getType(): Types\ExposeType
	{
		return Types\ExposeType::from($this->type);
	}

	public function getLengthMin(): int|null
	{
		return $this->lengthMin;
	}

	public function getLengthMax(): int|null
	{
		return $this->length_max;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'length_min' => $this->getLengthMin(),
				'length_max' => $this->getLengthMax(),
			],
		);
	}

}
