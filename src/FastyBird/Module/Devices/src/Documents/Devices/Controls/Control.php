<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Devices\Controls;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Device control document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Controls\Control::class)]
#[CoreDocuments\Mapping\RoutingMap([
	Devices\Types\RoutingKey::DEVICE_CONTROL_DOCUMENT_REPORTED->value,
	Devices\Types\RoutingKey::DEVICE_CONTROL_DOCUMENT_CREATED->value,
	Devices\Types\RoutingKey::DEVICE_CONTROL_DOCUMENT_UPDATED->value,
	Devices\Types\RoutingKey::DEVICE_CONTROL_DOCUMENT_DELETED->value,
])]
final readonly class Control implements DevicesDocuments\Document, CoreDocuments\Owner, CoreDocuments\CreatedAt, CoreDocuments\UpdatedAt
{

	use CoreDocuments\HasOwner;
	use CoreDocuments\HasCreatedAt;
	use CoreDocuments\HasUpdatedAt;

	public function __construct(
		private Uuid\UuidInterface $id,
		private Uuid\UuidInterface $device,
		#[CoreDocuments\Mapping\RefuseBlank]
		private string $name,
		#[CoreDocuments\Mapping\MapBlankToNull]
		protected Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		protected DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
		protected DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
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
			'source' => $this->getSource()->value,
			'device' => $this->getDevice()->toString(),
			'name' => $this->getName(),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	#[Override]
	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

}
