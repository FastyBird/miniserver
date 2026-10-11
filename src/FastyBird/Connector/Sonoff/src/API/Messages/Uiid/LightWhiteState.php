<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use Orisai\ObjectMapper;

/**
 * White color state entity
 */
final readonly class LightWhiteState implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 1, max: 100, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('br')]
		private int $brightness,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true)]
		#[ObjectMapper\Modifiers\FieldName('ct')]
		private int $temperature,
	)
	{
	}

	public function getBrightness(): int
	{
		return $this->brightness;
	}

	public function getTemperature(): int
	{
		return $this->temperature;
	}

	/**
	 * @return array<string, int>
	 */
	public function toArray(): array
	{
		return [
			'brightness' => $this->getBrightness(),
			'temperature' => $this->getTemperature(),
		];
	}

}
