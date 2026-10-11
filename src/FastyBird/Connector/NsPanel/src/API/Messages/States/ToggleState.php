<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Toggle control capability state
 */
final readonly class ToggleState implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Toggle::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::TOGGLE_STATE->value)]
		private Types\Payloads\Toggle $toggleState,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Startup::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::STARTUP->value)]
		private Types\Payloads\Startup|null $startup = null,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::TOGGLE;
	}

	public function getStartup(): Types\Payloads\Startup|null
	{
		return $this->startup;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::TOGGLE_STATE->value => $this->toggleState,
			Types\Attribute::STARTUP->value => $this->getStartup(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->toggleState->value,
			'startup' => $this->getStartup()?->value,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::TOGGLE_STATE->value} = $this->toggleState->value;

		if ($this->getStartup() !== null) {
			$json->{Types\Attribute::STARTUP->value} = $this->getStartup()->value;
		}

		return $json;
	}

}
