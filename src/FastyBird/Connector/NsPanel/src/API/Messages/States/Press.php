<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Press detection capability state
 */
final readonly class Press implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Press::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::PRESS->value)]
		private Types\Payloads\Press $press,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::PRESS;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::PRESS->value => $this->press,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->press->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::PRESS->value} = $this->press->value;

		return $json;
	}

}
