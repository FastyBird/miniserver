<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Color temperature control capability state
 */
final readonly class ColorTemperature implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_TEMPERATURE->value)]
		private int $colorTemperature,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::COLOR_TEMPERATURE;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::COLOR_TEMPERATURE->value => $this->colorTemperature,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->colorTemperature,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::COLOR_TEMPERATURE->value} = $this->colorTemperature;

		return $json;
	}

}
