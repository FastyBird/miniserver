<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Enum type expose type message
 */
final class EnumType extends Type
{

	/**
	 * @param array<int, string> $values
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2MqttTypes\ExposeType::ENUM->value])]
		private readonly string $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $values = [],
	)
	{
		parent::__construct($name, $label, $property, $access);
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): Zigbee2MqttTypes\ExposeType
	{
		return Zigbee2MqttTypes\ExposeType::from($this->type);
	}

	#[Override]
	public function getDataType(): ValuesTypes\DataType
	{
		return ValuesTypes\DataType::ENUM;
	}

	/**
	 * @return array<int, string>
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	#[Override]
	public function getFormat(): array|null
	{
		/** @var array<int, string> $values */
		$values = $this->getValues();

		return $values === [] ? null : $values;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'values' => $this->getValues(),
			],
		);
	}

}
