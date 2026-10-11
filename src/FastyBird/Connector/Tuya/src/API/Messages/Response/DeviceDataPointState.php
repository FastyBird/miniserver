<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI device data point state message
 */
final readonly class DeviceDataPointState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $code,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
		])]
		private string|int|float|bool $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $type = null,
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getValue(): float|bool|int|string
	{
		return $this->value;
	}

	public function getType(): string|null
	{
		return $this->type;
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
			'type' => $this->getType(),
		];
	}

}
