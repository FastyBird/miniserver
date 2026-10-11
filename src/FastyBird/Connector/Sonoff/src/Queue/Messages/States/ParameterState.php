<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queue\Messages\States;

use FastyBird\Connector\Sonoff\Queue;
use Orisai\ObjectMapper;

/**
 * Device or channel parameter state entity
 */
abstract class ParameterState implements Queue\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly float|int|string|bool|null $value,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): float|int|string|bool|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'value' => $this->getValue(),
		];
	}

}
