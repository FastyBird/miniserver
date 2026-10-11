<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
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

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::BATTERY;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::BATTERY->value => $this->battery,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->battery,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::BATTERY->value} = $this->battery;

		return $json;
	}

}
