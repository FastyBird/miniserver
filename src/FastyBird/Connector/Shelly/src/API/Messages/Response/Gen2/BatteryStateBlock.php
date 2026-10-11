<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device battery state message
 */
final readonly class BatteryStateBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		)]
		#[ObjectMapper\Modifiers\FieldName('V')]
		private float|null $voltage,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		)]
		private int|null $percent,
	)
	{
	}

	public function getVoltage(): float|null
	{
		return $this->voltage;
	}

	public function getPercent(): int|null
	{
		return $this->percent;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'voltage' => $this->getVoltage(),
			'percent' => $this->getPercent(),
		];
	}

	/**
	 * @return array<string, float|int|null>
	 */
	public function toState(): array
	{
		return [
			'battery_voltage' => $this->getVoltage(),
			'battery_percent' => $this->getPercent(),
		];
	}

}
