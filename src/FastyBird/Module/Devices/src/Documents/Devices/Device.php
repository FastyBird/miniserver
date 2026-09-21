<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Module\Devices\Documents\Devices;

use DateTimeInterface;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Device document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[ApplicationDocuments\Mapping\InheritanceType('SINGLE_TABLE')]
#[ApplicationDocuments\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[ApplicationDocuments\Mapping\MappedSuperclass]
#[ExchangeDocuments\Mapping\RoutingMap([
	Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_REPORTED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Device implements Documents\Document, ApplicationDocuments\Owner, ApplicationDocuments\CreatedAt, ApplicationDocuments\UpdatedAt
{

	use ApplicationDocuments\TOwner;
	use ApplicationDocuments\TCreatedAt;
	use ApplicationDocuments\TUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $parents
	 * @param array<Uuid\UuidInterface> $children
	 * @param array<Uuid\UuidInterface> $properties
	 * @param array<Uuid\UuidInterface> $controls
	 * @param array<Uuid\UuidInterface> $channels
	 */
	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\DeviceCategory::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\DeviceCategory::class),
		])]
		private readonly Types\DeviceCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $comment = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\UuidValue(),
		)]
		private readonly array $parents = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\UuidValue(),
		)]
		private readonly array $children = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\UuidValue(),
		)]
		private readonly array $properties = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\UuidValue(),
		)]
		private readonly array $controls = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\UuidValue(),
		)]
		private readonly array $channels = [],
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\DeviceCategory
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

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getParents(): array
	{
		return $this->parents;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
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

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChannels(): array
	{
		return $this->channels;
	}

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
			'connector' => $this->getConnector()->toString(),
			'parents' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getParents(),
			),
			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
			'channels' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChannels(),
			),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

}
