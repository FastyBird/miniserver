<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Channels;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Types;
use Override;
use Ramsey\Uuid;
use function array_map;

/**
 * Channel document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Channel::class)]
#[CoreDocuments\Mapping\InheritanceType('SINGLE_TABLE')]
#[CoreDocuments\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[CoreDocuments\Mapping\MappedSuperclass]
#[CoreDocuments\Mapping\RoutingMap([
	Types\RoutingKey::CHANNEL_DOCUMENT_REPORTED->value,
	Types\RoutingKey::CHANNEL_DOCUMENT_CREATED->value,
	Types\RoutingKey::CHANNEL_DOCUMENT_UPDATED->value,
	Types\RoutingKey::CHANNEL_DOCUMENT_DELETED->value,
])]
abstract class Channel implements
	DevicesDocuments\Document,
	CoreDocuments\Owner,
	CoreDocuments\CreatedAt,
	CoreDocuments\UpdatedAt
{

	use CoreDocuments\HasOwner;
	use CoreDocuments\HasCreatedAt;
	use CoreDocuments\HasUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $properties
	 * @param array<Uuid\UuidInterface> $controls
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Types\ChannelCategory $category,
		#[CoreDocuments\Mapping\RefuseBlank]
		private readonly string $identifier,
		private readonly Uuid\UuidInterface $device,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private readonly string|null $name = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private readonly string|null $comment = null,
		private readonly array $properties = [],
		private readonly array $controls = [],
		#[CoreDocuments\Mapping\MapBlankToNull]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\ChannelCategory
	{
		return $this->category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getControls(): array
	{
		return $this->controls;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'source' => $this->getSource()->value,
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'device' => $this->getDevice()->toString(),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
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
