<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Humidity detection capability state
 */
final readonly class Humidity implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::HUMIDITY->value)]
		private int $humidity,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::HUMIDITY;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::HUMIDITY->value => $this->humidity,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->humidity,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::HUMIDITY->value} = $this->humidity;

		return $json;
	}

}
