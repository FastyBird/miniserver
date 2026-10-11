<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Temperature detection capability state
 */
final readonly class Temperature implements State
{

	public function __construct(
		#[ObjectMapper\Rules\FloatValue(min: -40, max: 80)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::TEMPERATURE->value)]
		private float $temperature,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::TEMPERATURE;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::TEMPERATURE->value => $this->temperature,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->temperature,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::TEMPERATURE->value} = $this->temperature;

		return $json;
	}

}
