<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Triggers\Controls;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Trigger control action document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Documents\Mapping\Document]
#[Documents\Mapping\RoutingMap([
	Triggers\Constants::MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY,
])]
final readonly class Action implements Documents\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\TriggerAction::class)]
		private Types\TriggerAction $action,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $trigger,
		#[ApplicationObjectMapper\UuidValue()]
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
