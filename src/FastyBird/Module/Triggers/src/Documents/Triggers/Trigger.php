<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Trigger document
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Trigger::class)]
#[Documents\Mapping\InheritanceType('SINGLE_TABLE')]
#[Documents\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[Documents\Mapping\MappedSuperclass]
#[Documents\Mapping\RoutingMap([
	Triggers\Types\RoutingKey::TRIGGER_DOCUMENT_REPORTED->value,
	Triggers\Types\RoutingKey::TRIGGER_DOCUMENT_CREATED->value,
	Triggers\Types\RoutingKey::TRIGGER_DOCUMENT_UPDATED->value,
	Triggers\Types\RoutingKey::TRIGGER_DOCUMENT_DELETED->value,
])]
abstract class Trigger implements Documents\Document, Documents\Owner
{

	use Documents\HasOwner;

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		#[Documents\Mapping\RefuseBlank]
		private readonly string $type,
		#[Documents\Mapping\RefuseBlank]
		private readonly string $name,
		#[Documents\Mapping\MapBlankToNull]
		private readonly string|null $comment = null,
		private readonly bool $enabled = false,
		#[Documents\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_triggered')]
		private readonly bool|null $isTriggered = null,
		#[Documents\Mapping\MapBlankToNull]
		protected readonly Uuid\UuidInterface|null $owner = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function isTriggered(): bool|null
	{
		return $this->isTriggered;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
