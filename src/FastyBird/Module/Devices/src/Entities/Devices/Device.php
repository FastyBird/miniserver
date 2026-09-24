<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.01.17
 */

namespace FastyBird\Module\Devices\Entities\Devices;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\SimpleAuth as SimpleAuthEntities;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Types;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_devices',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Devices',
	],
)]
#[ORM\Index(columns: ['device_identifier'], name: 'device_identifier_idx')]
#[ORM\Index(columns: ['device_name'], name: 'device_name_idx')]
#[ORM\UniqueConstraint(name: 'device_identifier_unique', columns: ['device_identifier', 'connector_id'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'device_type', type: 'string', length: 100)]
// Seeded with the subtype this module owns; the rest are contributed at runtime by
// Core\Application's EntityDiscriminator from #[DiscriminatorEntry] on other packages.
//
// The map must be non-empty here. ClassMetadataFactory calls addDefaultDiscriminatorMap()
// before dispatching loadClassMetadata, and only when the map is empty -- and that default
// derives keys from short class names, which collide across packages that all name their
// entity the same, so it throws duplicate discriminator entry before any subscriber runs.
// An explicit map skips it, which is what the doctrine/orm patch used to achieve by
// deferring the call.
#[ORM\DiscriminatorMap([DevicesEntities\Devices\Generic::TYPE => DevicesEntities\Devices\Generic::class])]
#[ORM\MappedSuperclass]
abstract class Device implements DevicesEntities\Entity,
	DevicesEntities\EntityParams,
	SimpleAuthEntities\Owner,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use DevicesEntities\TEntity;
	use DevicesEntities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'device_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'device_category',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: Types\DeviceCategory::class,
		options: ['default' => Types\DeviceCategory::GENERIC],
	)]
	protected Types\DeviceCategory $category;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'device_identifier', type: 'string', length: 50, nullable: false)]
	protected string $identifier;

	/** @var Common\Collections\Collection<int, Device> */
	#[Attribute\Crud(writable: true)]
	#[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'children')]
	#[ORM\JoinTable(
		name: 'fb_devices_module_devices_children',
		joinColumns: [
			new ORM\JoinColumn(
				name: 'child_device',
				referencedColumnName: 'device_id',
				onDelete: 'CASCADE',
			),
		],
		inverseJoinColumns: [
			new ORM\JoinColumn(
				name: 'parent_device',
				referencedColumnName: 'device_id',
				onDelete: 'CASCADE',
			),
		],
	)]
	protected Common\Collections\Collection $parents;

	/** @var Common\Collections\Collection<int, Device> */
	#[ORM\ManyToMany(
		targetEntity: self::class,
		mappedBy: 'parents',
		cascade: ['remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $children;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'device_name', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $name = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'device_comment', type: 'text', nullable: true, options: ['default' => null])]
	protected string|null $comment = null;

	/** @var Common\Collections\Collection<int, DevicesEntities\Channels\Channel> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'device',
		targetEntity: DevicesEntities\Channels\Channel::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $channels;

	/** @var Common\Collections\Collection<int, DevicesEntities\Devices\Controls\Control> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'device',
		targetEntity: DevicesEntities\Devices\Controls\Control::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $controls;

	/** @var Common\Collections\Collection<int, DevicesEntities\Devices\Properties\Property> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'device',
		targetEntity: DevicesEntities\Devices\Properties\Property::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $properties;

	#[Attribute\Crud(writable: true)]
	#[ORM\ManyToOne(
		targetEntity: DevicesEntities\Connectors\Connector::class,
		cascade: ['persist'],
		inversedBy: 'devices',
	)]
	#[ORM\JoinColumn(
		name: 'connector_id',
		referencedColumnName: 'connector_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	protected DevicesEntities\Connectors\Connector $connector;

	public function __construct(
		string $identifier,
		DevicesEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;
		$this->name = $name;

		$this->category = Types\DeviceCategory::GENERIC;

		$this->connector = $connector;

		$this->parents = new Common\Collections\ArrayCollection();
		$this->children = new Common\Collections\ArrayCollection();
		$this->channels = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
		$this->properties = new Common\Collections\ArrayCollection();
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\DeviceCategory
	{
		return $this->category;
	}

	public function setCategory(Types\DeviceCategory $category): void
	{
		$this->category = $category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): void
	{
		$this->name = $name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function setComment(string|null $comment = null): void
	{
		$this->comment = $comment;
	}

	public function getConnector(): DevicesEntities\Connectors\Connector
	{
		return $this->connector;
	}

	public function setConnector(DevicesEntities\Connectors\Connector $connector): void
	{
		$this->connector = $connector;
	}

	/**
	 * @return array<Device>
	 */
	public function getParents(): array
	{
		return $this->parents->toArray();
	}

	/**
	 * @param array<Device>|Utils\ArrayHash<Device> $parents
	 */
	public function setParents(array|Utils\ArrayHash $parents): void
	{
		$this->parents = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($parents as $entity) {
			// ...and assign them to collection
			$this->addParent($entity);
		}
	}

	public function addParent(self $device): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->parents->contains($device)) {
			// ...and assign it to collection
			$this->parents->add($device);
		}

		$device->addChild($this);
	}

	public function hasParent(self $device): bool
	{
		return $this->parents->contains($device);
	}

	/**
	 * @return array<Device>
	 */
	public function getChildren(): array
	{
		return $this->children->toArray();
	}

	/**
	 * @param array<Device> $children
	 */
	public function setChildren(array $children): void
	{
		$this->children = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($children as $entity) {
			// ...and assign them to collection
			$this->addChild($entity);
		}
	}

	public function addChild(self $child): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->children->contains($child)) {
			// ...and assign it to collection
			$this->children->add($child);
		}
	}

	/**
	 * @return array<DevicesEntities\Channels\Channel>
	 */
	public function getChannels(): array
	{
		return $this->channels->toArray();
	}

	/**
	 * @param array<DevicesEntities\Channels\Channel> $channels
	 */
	public function setChannels(array $channels = []): void
	{
		$this->channels = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($channels as $entity) {
			// ...and assign them to collection
			$this->addChannel($entity);
		}
	}

	public function addChannel(DevicesEntities\Channels\Channel $channel): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->channels->contains($channel)) {
			// ...and assign it to collection
			$this->channels->add($channel);
		}
	}

	/**
	 * @return array<DevicesEntities\Devices\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<DevicesEntities\Devices\Controls\Control> $controls
	 */
	public function setControls(array $controls = []): void
	{
		$this->controls = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($controls as $entity) {
			// ...and assign them to collection
			$this->addControl($entity);
		}
	}

	public function addControl(DevicesEntities\Devices\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	/**
	 * @return array<DevicesEntities\Devices\Properties\Property>
	 */
	public function getProperties(): array
	{
		return $this->properties->toArray();
	}

	/**
	 * @param array<DevicesEntities\Devices\Properties\Property> $properties
	 */
	public function setProperties(array $properties = []): void
	{
		$this->properties = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($properties as $entity) {
			// ...and assign them to collection
			$this->addProperty($entity);
		}
	}

	public function addProperty(DevicesEntities\Devices\Properties\Property $property): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->properties->contains($property)) {
			// ...and assign it to collection
			$this->properties->add($property);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),

			'connector' => $this->getConnector()->getId()->toString(),

			'parents' => array_map(
				static fn (self $parent): string => $parent->getId()->toString(),
				$this->getParents(),
			),
			'children' => array_map(
				static fn (self $child): string => $child->getId()->toString(),
				$this->getChildren(),
			),

			'properties' => array_map(
				static fn (DevicesEntities\Devices\Properties\Property $property): string => $property->getId()->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (DevicesEntities\Devices\Controls\Control $control): string => $control->getId()->toString(),
				$this->getControls(),
			),
			'channels' => array_map(
				static fn (DevicesEntities\Channels\Channel $channel): string => $channel->getId()->toString(),
				$this->getChannels(),
			),

			'owner' => $this->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
