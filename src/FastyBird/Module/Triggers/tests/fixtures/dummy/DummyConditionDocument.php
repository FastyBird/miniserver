<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Types;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\Document(entity: DummyConditionEntity::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: DummyConditionEntity::TYPE)]
final class DummyConditionDocument extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		string $type,
		bool $enabled,
		#[Configurator\MapFromKey('watch_item')]
		private readonly Uuid\UuidInterface $watchItem,
		#[CoreDocuments\Mapping\RefuseBlank]
		private readonly string $operand,
		private readonly Types\ConditionOperator $operator,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_fulfilled')]
		bool|null $isFulfilled = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return DummyConditionEntity::TYPE;
	}

	public function getWatchItem(): Uuid\UuidInterface
	{
		return $this->watchItem;
	}

	public function getOperand(): string
	{
		return $this->operand;
	}

	public function getOperator(): Types\ConditionOperator
	{
		return $this->operator;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'watch_item' => $this->getWatchItem()->toString(),
			'operand' => $this->getOperand(),
			'operator' => $this->getOperator()->value,
		]);
	}

}
