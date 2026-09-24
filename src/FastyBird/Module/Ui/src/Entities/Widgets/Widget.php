<?php declare(strict_types = 1);

/**
 * Widget.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\SimpleAuth as SimpleAuthEntities;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities as UiEntities;
use FastyBird\Module\Ui\Entities\Dashboards\Tabs\Tab;
use FastyBird\Module\Ui\Exceptions;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets',
	],
)]
#[ORM\Index(columns: ['widget_type'], name: 'widget_type_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'widget_type', type: 'string', length: 100)]
// Seeded with a subtype this module owns; the rest arrive at runtime from
// Core\Application's EntityDiscriminator. The map has to be non-empty: ClassMetadataFactory
// calls addDefaultDiscriminatorMap() before dispatching loadClassMetadata and only when the
// map is empty, and that default keys entries on short class names. An explicit map skips
// it, which is what the doctrine/orm patch used to do by deferring the call.
#[ORM\DiscriminatorMap([UiEntities\Widgets\AnalogSensor::TYPE => UiEntities\Widgets\AnalogSensor::class])]
#[ORM\MappedSuperclass]
abstract class Widget implements UiEntities\Entity,
	UiEntities\EntityParams,
	SimpleAuthEntities\Owner,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use UiEntities\TEntity;
	use UiEntities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'widget_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'widget_identifier', type: 'string', nullable: false)]
	protected string $identifier;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'widget_name', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $name = null;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\OneToOne(
		mappedBy: 'widget',
		targetEntity: UiEntities\Widgets\Displays\Display::class,
		cascade: ['persist', 'remove'],
	)]
	protected Displays\Display $display;

	/** @var Common\Collections\Collection<int, Tab> */
	#[Attribute\Crud(writable: true)]
	#[ORM\ManyToMany(
		targetEntity: UiEntities\Dashboards\Tabs\Tab::class,
		mappedBy: 'widgets',
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	#[ORM\OrderBy(['priority' => 'ASC'])]
	protected Common\Collections\Collection $tabs;

	/** @var Common\Collections\Collection<int, UiEntities\Groups\Group> */
	#[Attribute\Crud(writable: true)]
	#[ORM\ManyToMany(
		targetEntity: UiEntities\Groups\Group::class,
		mappedBy: 'widgets',
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	#[ORM\OrderBy(['priority' => 'ASC'])]
	protected Common\Collections\Collection $groups;

	/** @var Common\Collections\Collection<int, UiEntities\Widgets\DataSources\DataSource> */
	#[Attribute\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'widget',
		targetEntity: UiEntities\Widgets\DataSources\DataSource::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	#[ORM\OrderBy(['id' => 'ASC'])]
	protected Common\Collections\Collection $dataSources;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->tabs = new Common\Collections\ArrayCollection();
		$this->groups = new Common\Collections\ArrayCollection();
		$this->dataSources = new Common\Collections\ArrayCollection();
	}

	abstract public static function getType(): string;

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

	public function getDisplay(): UiEntities\Widgets\Displays\Display
	{
		return $this->display;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setDisplay(UiEntities\Widgets\Displays\Display $display): void
	{
		$isAllowed = false;

		foreach ($this->getAllowedDisplayTypes() as $displayType) {
			if ($display instanceof $displayType) {
				$isAllowed = true;
			}
		}

		if (!$isAllowed) {
			throw new Exceptions\InvalidArgument('Provided display entity is not valid for this widget type');
		}

		$this->display = $display;
	}

	public function addDataSource(UiEntities\Widgets\DataSources\DataSource $dataSource): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->dataSources->contains($dataSource)) {
			// ...and assign it to collection
			$this->dataSources->add($dataSource);
		}
	}

	/**
	 * @return array<UiEntities\Widgets\DataSources\DataSource>
	 */
	public function getDataSources(): array
	{
		return $this->dataSources->toArray();
	}

	/**
	 * @param array<UiEntities\Widgets\DataSources\DataSource> $dataSources
	 */
	public function setDataSources(array $dataSources = []): void
	{
		$this->dataSources = new Common\Collections\ArrayCollection();

		foreach ($dataSources as $entity) {
			$this->dataSources->add($entity);
		}
	}

	public function removeDataSource(UiEntities\Widgets\DataSources\DataSource $dataSource): void
	{
		// Check if collection contain removing entity...
		if ($this->dataSources->contains($dataSource)) {
			// ...and remove it from collection
			$this->dataSources->removeElement($dataSource);
		}
	}

	public function addTab(UiEntities\Dashboards\Tabs\Tab $tab): void
	{
		$this->tabs = new Common\Collections\ArrayCollection();

		$tab->addWidget($this);

		// ...and assign it to collection
		$this->tabs->add($tab);
	}

	/**
	 * @return array<Tab>
	 */
	public function getTabs(): array
	{
		return $this->tabs->toArray();
	}

	/**
	 * @param array<Tab> $tabs
	 */
	public function setTabs(array $tabs = []): void
	{
		$this->tabs = new Common\Collections\ArrayCollection();

		foreach ($tabs as $entity) {
			if (!$this->tabs->contains($entity)) {
				$entity->addWidget($this);

				// ...and assign them to collection
				$this->tabs->add($entity);
			}
		}
	}

	public function getTab(string $id): UiEntities\Dashboards\Tabs\Tab|null
	{
		$found = $this->tabs
			->filter(static fn (UiEntities\Dashboards\Tabs\Tab $row): bool => $id === $row->getId()->toString());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeTab(UiEntities\Dashboards\Tabs\Tab $tab): void
	{
		// Check if collection contain removing entity...
		if ($this->tabs->contains($tab)) {
			// ...and remove it from collection
			$this->tabs->removeElement($tab);
		}
	}

	public function addGroup(UiEntities\Groups\Group $group): void
	{
		$this->groups = new Common\Collections\ArrayCollection();

		$group->addWidget($this);

		// ...and assign it to collection
		$this->groups->add($group);
	}

	/**
	 * @return array<UiEntities\Groups\Group>
	 */
	public function getGroups(): array
	{
		return $this->groups->toArray();
	}

	/**
	 * @param array<UiEntities\Groups\Group> $groups
	 */
	public function setGroups(array $groups = []): void
	{
		$this->groups = new Common\Collections\ArrayCollection();

		foreach ($groups as $entity) {
			if (!$this->groups->contains($entity)) {
				$entity->addWidget($this);

				// ...and assign them to collection
				$this->groups->add($entity);
			}
		}
	}

	public function getGroup(string $id): UiEntities\Groups\Group|null
	{
		$found = $this->groups
			->filter(static fn (UiEntities\Groups\Group $row): bool => $id === $row->getId()->toString());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeGroup(UiEntities\Groups\Group $group): void
	{
		// Check if collection contain removing entity...
		if ($this->groups->contains($group)) {
			// ...and remove it from collection
			$this->groups->removeElement($group);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'type' => static::getType(),

			'tabs' => array_map(
				static fn (UiEntities\Dashboards\Tabs\Tab $tab): string => $tab->getId()->toString(),
				$this->getTabs(),
			),
			'groups' => array_map(
				static fn (UiEntities\Groups\Group $group): string => $group->getId()->toString(),
				$this->getGroups(),
			),
			'data_sources' => array_map(
				static fn (UiEntities\Widgets\DataSources\DataSource $dataSource): string => $dataSource->getId()->toString(),
				$this->getDataSources(),
			),
			'display' => $this->getDisplay()->getId()->toString(),

			'owner' => $this->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	/**
	 * @return array<class-string>
	 */
	public function getAllowedDisplayTypes(): array
	{
		return [];
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::UI;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
