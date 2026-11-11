<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers\Controls;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Types;
use Override;
use Ramsey\Uuid;

/**
 * Trigger control action document
 */
#[Documents\Mapping\Document]
#[Documents\Mapping\RoutingMap([
	Types\RoutingKey::TRIGGER_CONTROL_ACTION->value,
])]
final readonly class Action implements Documents\Document
{

	public function __construct(
		private Types\TriggerAction $action,
		private Uuid\UuidInterface $trigger,
		private Uuid\UuidInterface $control,
		#[Documents\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('expected_value')]
		// No int in the union: the rule tried a float before an int, so an int was always a float
		private bool|float|string|null $expectedValue = null,
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
