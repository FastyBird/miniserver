<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers\Controls;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Trigger control document
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Controls\Control::class)]
#[Documents\Mapping\RoutingMap([
	Triggers\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
])]
final class Control implements Documents\Document, Documents\Owner
{

	use Documents\HasOwner;

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $trigger,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[Rules\UuidValue()]
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

	public function getName(): string
	{
		return $this->name;
	}

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
