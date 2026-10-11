<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Color control capability state
 */
final readonly class ColorRgb implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_RED->value)]
		private int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_GREEN->value)]
		private int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_BLUE->value)]
		private int $blue,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::COLOR_RGB;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::COLOR_RED->value => $this->red,
			Types\Attribute::COLOR_GREEN->value => $this->green,
			Types\Attribute::COLOR_BLUE->value => $this->blue,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'red' => $this->red,
			'green' => $this->green,
			'blue' => $this->blue,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::COLOR_RED->value} = $this->red;
		$json->{Types\Attribute::COLOR_GREEN->value} = $this->green;
		$json->{Types\Attribute::COLOR_BLUE->value} = $this->blue;

		return $json;
	}

}
