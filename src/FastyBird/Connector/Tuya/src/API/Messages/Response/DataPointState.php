<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Data point state message
 */
final class DataPointState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $code,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|int|string|bool|null $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $dps,
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

	public function getDps(): string|null
	{
		return $this->dps;
	}

	public function setDps(string|null $dps): void
	{
		$this->dps = $dps;
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
			'dps' => $this->getDps(),
		];
	}

}
