<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Percentage control capability state
 */
final readonly class Percentage implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::PERCENTAGE->value)]
		private int $percentage,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::PERCENTAGE;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::PERCENTAGE->value => $this->percentage,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->percentage,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::PERCENTAGE->value} = $this->percentage;

		return $json;
	}

}
