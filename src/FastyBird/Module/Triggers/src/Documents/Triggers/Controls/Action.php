<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers\Controls;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Module\Triggers\Types;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Trigger control action document
 */
#[Documents\Mapping\Document]
#[Documents\Mapping\RoutingMap([
	Types\RoutingKey::TRIGGER_CONTROL_ACTION->value,
])]
final readonly class Action implements Documents\Document, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\TriggerAction::class)]
		private Types\TriggerAction $action,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $trigger,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $control,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private bool|float|int|string|null $expectedValue = null,
	)
	{
	}

	public function getAction(): Types\TriggerAction
	{
		return $this->action;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	public function getControl(): Uuid\UuidInterface
	{
		return $this->control;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'action' => $this->getAction()->value,
			'trigger' => $this->getTrigger()->toString(),
			'control' => $this->getControl()->toString(),
			'expected_value' => $this->getExpectedValue(),
		];
	}

}
