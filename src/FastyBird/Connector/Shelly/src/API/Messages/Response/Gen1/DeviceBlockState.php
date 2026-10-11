<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 1 device block state message
 */
final readonly class DeviceBlockState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private int $block,
		#[ObjectMapper\Rules\IntValue()]
		private int $sensor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|string|null $value,
	)
	{
	}

	public function getBlock(): int
	{
		return $this->block;
	}

	public function getSensor(): int
	{
		return $this->sensor;
	}

	public function getValue(): float|int|string|null
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
			'block' => $this->getBlock(),
			'sensor' => $this->getSensor(),
			'value' => $this->getValue(),
		];
	}

}
