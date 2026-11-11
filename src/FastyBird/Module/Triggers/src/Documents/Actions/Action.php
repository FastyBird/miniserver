<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Actions;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Action document
 */
#[Documents\Mapping\Document(entity: Entities\Actions\Action::class)]
#[Documents\Mapping\InheritanceType('SINGLE_TABLE')]
#[Documents\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[Documents\Mapping\MappedSuperclass]
#[Documents\Mapping\RoutingMap([
	Triggers\Types\RoutingKey::ACTION_DOCUMENT_REPORTED->value,
	Triggers\Types\RoutingKey::ACTION_DOCUMENT_CREATED->value,
	Triggers\Types\RoutingKey::ACTION_DOCUMENT_UPDATED->value,
	Triggers\Types\RoutingKey::ACTION_DOCUMENT_DELETED->value,
])]
abstract class Action implements Documents\Document, Documents\Owner
{

	use Documents\HasOwner;

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Uuid\UuidInterface $trigger,
		private readonly bool $enabled,
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

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	abstract public static function getType(): string;

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
			'trigger' => $this->getTrigger()->toString(),
			'type' => static::getType(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
