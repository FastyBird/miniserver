<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Power on state (Power Supply) capability state
 */
final readonly class Startup implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Startup::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::STARTUP->value)]
		private Types\Payloads\Startup $startup,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::STARTUP;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::STARTUP->value => $this->startup,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->startup->value,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::STARTUP->value} = $this->startup->value;

		return $json;
	}

}
