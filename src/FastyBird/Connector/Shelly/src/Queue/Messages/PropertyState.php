<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Device or channel property state message
 */
final readonly class PropertyState implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private float|int|string|bool|null $value,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
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
			'identifier' => $this->getIdentifier(),
			'value' => $this->getValue(),
		];
	}

}
