<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * State detection capability state
 */
final readonly class Detect implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::DETECTED->value)]
		private bool $detected,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::DETECT;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::DETECTED->value => $this->detected,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->detected,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::DETECTED->value} = $this->detected;

		return $json;
	}

}
