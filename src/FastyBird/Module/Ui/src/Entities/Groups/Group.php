<?php declare(strict_types = 1);

/**
 * Group.php
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

namespace FastyBird\Module\Ui\Entities\Groups;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\DoctrineTimestampable;
use FastyBird\Core\Entities\SimpleAuth as SimpleAuthEntities;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_groups',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Widget groups',
	],
)]
#[ORM\Index(columns: ['group_name'], name: 'group_name_idx')]
class Group implements Entities\Entity,
	Entities\EntityParams,
	SimpleAuthEntities\Owner,
	DoctrineTimestampable\IEntityCreated, DoctrineTimestampable\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use DoctrineTimestampable\TEntityCreated;
	use DoctrineTimestampable\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'group_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'group_identifier', type: 'string', nullable: false)]
	private string $identifier;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'group_name', type: 'string', nullable: true, options: ['default' => null])]
	private string|null $name = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'group_comment', type: 'text', nullable: true, options: ['default' => null])]
	private string|null $comment = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'group_priority', type: 'integer', nullable: false, options: ['default' => 0])]
	private int $priority = 0;

	/** @var Common\Collections\Collection<int, Entities\Widgets\Widget> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToMany(targetEntity: Entities\Widgets\Widget::class, inversedBy: 'groups')]
	#[ORM\JoinTable(
		name: 'fb_ui_module_widgets_groups',
		joinColumns: [
			new ORM\JoinColumn(
				name: 'group_id',
				referencedColumnName: 'group_id',
				onDelete: 'CASCADE',
			),
		],
		inverseJoinColumns: [
			new ORM\JoinColumn(
				name: 'widget_id',
				referencedColumnName: 'widget_id',
				onDelete: 'CASCADE',
			),
		],
	)]
	#[ORM\OrderBy(['id' => 'ASC'])]
	private Common\Collections\Collection $widgets;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->widgets = new Common\Collections\ArrayCollection();
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

	public function addWidget(Entities\Widgets\Widget $widget): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->widgets->contains($widget)) {
			// ...and assign it to collection
			$this->widgets->add($widget);
		}
	}

	/**
	 * @return array<Entities\Widgets\Widget>
	 */
	public function getWidgets(): array
	{
		return $this->widgets->toArray();
	}

	/**
	 * @param array<Entities\Widgets\Widget> $widgets
	 */
	public function setWidgets(array $widgets = []): void
	{
		$this->widgets = new Common\Collections\ArrayCollection();

		foreach ($widgets as $entity) {
			$this->widgets->add($entity);
		}
	}

	public function getWidget(string $id): Entities\Widgets\Widget|null
	{
		$found = $this->widgets
			->filter(static fn (Entities\Widgets\Widget $row): bool => $id === $row->getId()->toString());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeWidget(Entities\Widgets\Widget $widget): void
	{
		// Check if collection contain removing entity...
		if ($this->widgets->contains($widget)) {
			// ...and remove it from collection
			$this->widgets->removeElement($widget);
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

			'widgets' => array_map(
				static fn (Entities\Widgets\Widget $widget): string => $widget->getId()->toString(),
				$this->getWidgets(),
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
