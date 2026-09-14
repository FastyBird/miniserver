<?php declare(strict_types = 1);

/**
 * Dashboard.php
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

namespace FastyBird\Module\Ui\Entities\Dashboards;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\SimpleAuth\Entities as SimpleAuthEntities;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use FastyBird\Library\DoctrineTimestampable;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Entities\Dashboards\Tabs\Tab;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_dashboards',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets dashboards',
	],
)]
#[ORM\Index(columns: ['dashboard_name'], name: 'dashboard_name_idx')]
class Dashboard implements Entities\Entity,
	Entities\EntityParams,
	SimpleAuthEntities\Owner,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'dashboard_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'dashboard_identifier', type: 'string', nullable: false)]
	private string $identifier;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'dashboard_name', type: 'string', nullable: true, options: ['default' => null])]
	private string|null $name = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'dashboard_comment', type: 'text', nullable: true, options: ['default' => null])]
	private string|null $comment = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'dashboard_priority', type: 'integer', nullable: false, options: ['default' => 0])]
	private int $priority = 0;

	/** @var Common\Collections\Collection<int, Tab> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'dashboard',
		targetEntity: Tabs\Tab::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	#[ORM\OrderBy(['priority' => 'ASC'])]
	protected Common\Collections\Collection $tabs;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->tabs = new Common\Collections\ArrayCollection();
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

	public function getPriority(): int
	{
		return $this->priority;
	}

	public function setPriority(int $priority): void
	{
		$this->priority = $priority;
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

		// Process all passed entities...
		foreach ($tabs as $entity) {
			// ...and assign them to collection
			$this->addTab($entity);
		}
	}

	public function addTab(Tabs\Tab $tab): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->tabs->contains($tab)) {
			// ...and assign it to collection
			$this->tabs->add($tab);
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
			'comment' => $this->getComment(),
			'priority' => $this->getPriority(),

			'tabs' => array_map(
				static fn (Tabs\Tab $tab): string => $tab->getId()->toString(),
				$this->getTabs(),
			),

			'owner' => $this->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::UI;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
