<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;

/**
 * Expose data row
 */
final readonly class SingleExposeData implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeDataType::SINGLE->value])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private bool|int|float|string|null $value = null,
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

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
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
			'value' => $this->getValue(),
		];
	}

}
