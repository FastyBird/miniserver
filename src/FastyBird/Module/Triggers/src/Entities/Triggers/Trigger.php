<?php declare(strict_types = 1);

/**
 * Trigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Entities\Triggers;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Triggers\Entities;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_triggers',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Actions triggers',
	],
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'trigger_type', type: 'string', length: 100)]
#[ORM\MappedSuperclass]
abstract class Trigger implements Entities\Entity,
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
	#[ORM\Column(name: 'trigger_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'trigger_name', type: 'string', length: 100, nullable: false)]
	protected string $name;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'trigger_comment', type: 'text', nullable: true, options: ['default' => null])]
	protected string|null $comment = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'trigger_enabled', type: 'boolean', length: 1, nullable: false, options: ['default' => true])]
	protected bool $enabled = true;

	/** @var Common\Collections\Collection<int, Entities\Actions\Action> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'trigger',
		targetEntity: Entities\Actions\Action::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $actions;

	/** @var Common\Collections\Collection<int, Entities\Notifications\Notification> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'trigger',
		targetEntity: Entities\Notifications\Notification::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $notifications;

	/** @var Common\Collections\Collection<int, Entities\Triggers\Controls\Control> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'trigger',
		targetEntity: Entities\Triggers\Controls\Control::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	private Common\Collections\Collection $controls;

	public function __construct(string $name, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->setName($name);

		$this->actions = new Common\Collections\ArrayCollection();
		$this->notifications = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
	}

	/**
	 * @return array<Entities\Actions\Action>
	 */
	public function getActions(): array
	{
		return $this->actions->toArray();
	}

	/**
	 * @param array<Entities\Actions\Action> $actions
	 */
	public function setActions(array $actions = []): void
	{
		$this->actions = new Common\Collections\ArrayCollection();

		foreach ($actions as $entity) {
			$this->addAction($entity);
		}
	}

	public function addAction(Entities\Actions\Action $action): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->actions->contains($action)) {
			// ...and assign it to collection
			$this->actions->add($action);
		}
	}

	public function getAction(string $id): Entities\Actions\Action|null
	{
		$found = $this->actions
			->filter(static fn (Entities\Actions\Action $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeAction(Entities\Actions\Action $action): void
	{
		// Check if collection contain removing entity...
		if ($this->actions->contains($action)) {
			// ...and remove it from collection
			$this->actions->removeElement($action);
		}
	}

	/**
	 * @return array<Entities\Notifications\Notification>
	 */
	public function getNotifications(): array
	{
		return $this->notifications->toArray();
	}

	/**
	 * @param array<Entities\Notifications\Notification> $notifications
	 */
	public function setNotifications(array $notifications = []): void
	{
		$this->notifications = new Common\Collections\ArrayCollection();

		foreach ($notifications as $entity) {
			$this->addNotification($entity);
		}
	}

	public function addNotification(Entities\Notifications\Notification $notification): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->notifications->contains($notification)) {
			// ...and assign it to collection
			$this->notifications->add($notification);
		}
	}

	public function getNotification(string $id): Entities\Notifications\Notification|null
	{
		$found = $this->notifications
			->filter(static fn (Entities\Notifications\Notification $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeNotification(Entities\Notifications\Notification $notification): void
	{
		// Check if collection contain removing entity...
		if ($this->notifications->contains($notification)) {
			// ...and remove it from collection
			$this->notifications->removeElement($notification);
		}
	}

	/**
	 * @return array<Entities\Triggers\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<Entities\Triggers\Controls\Control> $controls
	 */
	public function setControls(array $controls = []): void
	{
		$this->controls = new Common\Collections\ArrayCollection();

		foreach ($controls as $entity) {
			$this->addControl($entity);
		}
	}

	public function addControl(Entities\Triggers\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	public function getControl(string $name): Entities\Triggers\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Triggers\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeControl(Entities\Triggers\Controls\Control $control): void
	{
		// Check if collection contain removing entity...
		if ($this->controls->contains($control)) {
			// ...and remove it from collection
			$this->controls->removeElement($control);
		}
	}

	public function hasControl(string $name): bool
	{
		return $this->findControl($name) !== null;
	}

	public function findControl(string $name): Entities\Triggers\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Triggers\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() ? null : $found->first();
	}

	abstract public static function getType(): string;

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
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
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => static::getType(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),

			'owner' => $this->getOwnerId(),
		];
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
