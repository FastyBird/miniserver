<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Wireless signal strength detection capability state
 */
final readonly class Rssi implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: -200, max: 0, unsigned: false)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::RSSI->value)]
		private int $rssi,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::RSSI;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::RSSI->value => $this->rssi,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->rssi,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::RSSI->value} = $this->rssi;

		return $json;
	}

}
