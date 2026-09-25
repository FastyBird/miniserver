<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.01.20
 */

namespace FastyBird\Module\Devices\Entities\Connectors;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Security\Entities as SecurityEntities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Types;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_connectors',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Communication connectors',
	],
)]
#[ORM\Index(columns: ['connector_identifier'], name: 'connector_identifier_idx')]
#[ORM\Index(columns: ['connector_name'], name: 'connector_name_idx')]
#[ORM\Index(columns: ['connector_enabled'], name: 'connector_enabled_idx')]
#[ORM\UniqueConstraint(name: 'connector_identifier_unique', columns: ['connector_identifier'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'connector_type', type: 'string', length: 100)]
// Seeded with the subtype this module owns; the rest are contributed at runtime by
// Core\Application's EntityDiscriminator from #[DiscriminatorEntry] on connector packages.
//
// The map must be non-empty here. ClassMetadataFactory calls addDefaultDiscriminatorMap()
// before dispatching loadClassMetadata, and only when the map is empty -- and that default
// derives keys from short class names, which are all "connector" across the ten connector
// packages, so it throws duplicate discriminator entry before any subscriber can run. An
// explicit map skips it. This is what the removed doctrine/orm patch used to do by deferring
// the call, and unlike the patch it needs nothing from ORM internals.
#[ORM\DiscriminatorMap([DevicesEntities\Connectors\Generic::TYPE => DevicesEntities\Connectors\Generic::class])]
#[ORM\MappedSuperclass]
abstract class Connector implements DevicesEntities\Entity,
	DevicesEntities\EntityParams,
	SecurityEntities\Owner,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use DevicesEntities\TEntity;
	use DevicesEntities\TEntityParams;
	use SecurityEntities\HasOwner;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'connector_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'connector_category',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: Types\ConnectorCategory::class,
		options: ['default' => Types\ConnectorCategory::GENERIC],
	)]
	protected Types\ConnectorCategory $category;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'connector_identifier', type: 'string', length: 50, nullable: false)]
	protected string $identifier;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'connector_name', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $name = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'connector_comment', type: 'text', nullable: true, options: ['default' => null])]
	protected string|null $comment = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'connector_enabled', type: 'boolean', length: 1, nullable: false, options: ['default' => true])]
	protected bool $enabled = true;

	/** @var Common\Collections\Collection<int, DevicesEntities\Devices\Device> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'connector',
		targetEntity: DevicesEntities\Devices\Device::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $devices;

	/** @var Common\Collections\Collection<int, DevicesEntities\Connectors\Properties\Property> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'connector',
		targetEntity: DevicesEntities\Connectors\Properties\Property::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $properties;

	/** @var Common\Collections\Collection<int, DevicesEntities\Connectors\Controls\Control> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'connector',
		targetEntity: DevicesEntities\Connectors\Controls\Control::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $controls;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->category = Types\ConnectorCategory::GENERIC;

		$this->devices = new Common\Collections\ArrayCollection();
		$this->properties = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\ConnectorCategory
	{
		return $this->category;
	}

	public function setCategory(Types\ConnectorCategory $category): void
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

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return array<DevicesEntities\Devices\Device>
	 */
	public function getDevices(): array
	{
		return $this->devices->toArray();
	}

	/**
	 * @param array<DevicesEntities\Devices\Device> $devices
	 */
	public function setDevices(array $devices = []): void
	{
		$this->devices = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($devices as $entity) {
			// ...and assign them to collection
			$this->addDevice($entity);
		}
	}

	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->devices->contains($device)) {
			// ...and assign it to collection
			$this->devices->add($device);
		}
	}

	/**
	 * @return array<DevicesEntities\Connectors\Properties\Property>
	 */
	public function getProperties(): array
	{
		return $this->properties->toArray();
	}

	/**
	 * @param array<DevicesEntities\Connectors\Properties\Property> $properties
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

	public function addProperty(DevicesEntities\Connectors\Properties\Property $property): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->properties->contains($property)) {
			// ...and assign it to collection
			$this->properties->add($property);
		}
	}

	/**
	 * @return array<DevicesEntities\Connectors\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<DevicesEntities\Connectors\Controls\Control> $controls
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

	public function addControl(DevicesEntities\Connectors\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
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
			'enabled' => $this->isEnabled(),

			'properties' => array_map(
				static fn (DevicesEntities\Connectors\Properties\Property $property): string => $property->getId()->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (DevicesEntities\Connectors\Controls\Control $control): string => $control->getId()->toString(),
				$this->getControls(),
			),
			'devices' => array_map(
				static fn (DevicesEntities\Devices\Device $device): string => $device->getId()->toString(),
				$this->getDevices(),
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
