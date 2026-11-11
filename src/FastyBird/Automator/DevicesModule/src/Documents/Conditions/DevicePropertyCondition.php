<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Documents\Conditions;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device property condition document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Conditions\DevicePropertyCondition::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Conditions\DevicePropertyCondition::TYPE)]
final class DevicePropertyCondition extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		private readonly Uuid\UuidInterface $device,
		private readonly Uuid\UuidInterface $property,
		#[CoreDocuments\Mapping\RefuseBlank]
		private readonly string $operand,
		private readonly TriggersTypes\ConditionOperator $operator,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_fulfilled')]
		bool|null $isFulfilled = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Conditions\DevicePropertyCondition::TYPE;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function getOperator(): TriggersTypes\ConditionOperator
	{
		return $this->operator;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'operand' => $this->getOperand(),
			'operator' => $this->getOperator()->value,
		]);
	}

}
