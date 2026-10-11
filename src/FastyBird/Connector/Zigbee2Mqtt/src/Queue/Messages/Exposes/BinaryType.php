<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function array_merge;
use function is_bool;

/**
 * Binary type expose type message
 */
final class BinaryType extends Type
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2MqttTypes\ExposeType::BINARY->value])]
		private readonly string $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_on')]
		private readonly bool|string $valueOn,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_off')]
		private readonly bool|string $valueOff,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_toggle')]
		private readonly bool|string|null $valueToggle = null,
	)
	{
		parent::__construct($name, $label, $property, $access);
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function getType(): Zigbee2MqttTypes\ExposeType
	{
		return Zigbee2MqttTypes\ExposeType::from($this->type);
	}

	#[Override]
	public function getDataType(): ValuesTypes\DataType
	{
		if (is_bool($this->getValueOn()) && is_bool($this->getValueOff())) {
			return ValuesTypes\DataType::BOOLEAN;
		}

		return ValuesTypes\DataType::SWITCH;
	}

	public function getValueOn(): bool|string
	{
		return $this->valueOn;
	}

	public function getValueOff(): bool|string
	{
		return $this->valueOff;
	}

	public function getValueToggle(): bool|string|null
	{
		return $this->valueToggle;
	}

	#[Override]
	public function getFormat(): array|null
	{
		if (is_bool($this->getValueOn()) && is_bool($this->getValueOff())) {
			return parent::getFormat();
		}

		return array_merge(
			[
				[
					Payloads\Switcher::ON->value,
					$this->getValueOn(),
					$this->getValueOn(),
				],
				[
					Payloads\Switcher::OFF->value,
					$this->getValueOff(),
					$this->getValueOff(),
				],
			],
			[
				$this->getValueToggle() !== null
					? [
						Payloads\Switcher::TOGGLE->value,
						$this->getValueToggle(),
						$this->getValueToggle(),
					]
					: [],
			],
		);
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'value_on' => $this->getValueOn(),
				'value_off' => $this->getValueOff(),
				'value_toggle' => $this->getValueToggle(),
			],
		);
	}

}
