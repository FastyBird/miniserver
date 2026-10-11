<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Power state control capability state
 */
final readonly class PowerState implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Power::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::POWER_STATE->value)]
		private Types\Payloads\Power $powerState,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::POWER;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::POWER_STATE->value => $this->powerState,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->powerState->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::POWER_STATE->value} = $this->powerState->value;

		return $json;
	}

}
