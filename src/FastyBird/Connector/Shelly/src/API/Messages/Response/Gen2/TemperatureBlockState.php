<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Generation 2 device switch or cover component temperature state message
 */
final readonly class TemperatureBlockState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('tC')]
		private float|null $temperatureCelsius,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('tF')]
		private float|null $temperatureFahrenheit,
	)
	{
	}

	public function getTemperatureCelsius(): float|null
	{
		return $this->temperatureCelsius;
	}

	public function getTemperatureFahrenheit(): float|null
	{
		return $this->temperatureFahrenheit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'celsius' => $this->getTemperatureCelsius(),
			'fahrenheit' => $this->getTemperatureFahrenheit(),
		];
	}

	/**
	 * @return array<string, float>
	 */
	public function toState(): array
	{
		return array_merge(
			$this->getTemperatureCelsius() !== null ? ['temperature_celsius' => $this->getTemperatureCelsius()] : [],
			$this->getTemperatureFahrenheit() !== null ? ['temperature_fahrenheit' => $this->getTemperatureFahrenheit()] : [],
		);
	}

}
