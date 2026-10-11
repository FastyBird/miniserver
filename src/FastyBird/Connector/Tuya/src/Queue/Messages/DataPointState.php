<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Queue\Messages;

use Orisai\ObjectMapper;
use Override;

/**
 * Data point status message
 */
final readonly class DataPointState implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $code,
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

	public function getCode(): string
	{
		return $this->code;
	}

	public function getValue(): float|int|string|bool|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'value' => $this->getValue(),
		];
	}

}
