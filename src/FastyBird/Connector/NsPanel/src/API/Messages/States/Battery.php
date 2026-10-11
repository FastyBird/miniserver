<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Remaining battery detection capability state
 */
final readonly class Battery implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::BATTERY->value)]
		private int $battery,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::BATTERY;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::BATTERY->value => $this->battery,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->battery,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::BATTERY->value} = $this->battery;

		return $json;
	}

}
