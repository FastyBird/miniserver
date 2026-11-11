<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers\Controls;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Trigger control document
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Controls\Control::class)]
#[Documents\Mapping\RoutingMap([
	Triggers\Types\RoutingKey::TRIGGER_CONTROL_DOCUMENT_REPORTED->value,
	Triggers\Types\RoutingKey::TRIGGER_CONTROL_DOCUMENT_CREATED->value,
	Triggers\Types\RoutingKey::TRIGGER_CONTROL_DOCUMENT_UPDATED->value,
	Triggers\Types\RoutingKey::TRIGGER_CONTROL_DOCUMENT_DELETED->value,
])]
final readonly class Control implements Documents\Document, Documents\Owner
{

	use Documents\HasOwner;

	public function __construct(
		private Uuid\UuidInterface $id,
		private Uuid\UuidInterface $trigger,
		#[Documents\Mapping\RefuseBlank]
		private string $name,
		protected Uuid\UuidInterface|null $owner = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	public function getName(): string
	{
		return $this->name;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'name' => $this->getName(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
