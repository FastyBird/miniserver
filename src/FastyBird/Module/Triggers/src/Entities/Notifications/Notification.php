<?php declare(strict_types = 1);

/**
 * Notification.php
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

namespace FastyBird\Module\Triggers\Entities\Notifications;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_notifications',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Triggers notifications',
	],
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'notification_type', type: 'string', length: 100)]
// Seeded with a subtype this module owns; the rest arrive at runtime from
// Core\Application's EntityDiscriminator. The map has to be non-empty: ClassMetadataFactory
// calls addDefaultDiscriminatorMap() before dispatching loadClassMetadata and only when the
// map is empty, and that default keys entries on short class names. An explicit map skips
// it, which is what the doctrine/orm patch used to do by deferring the call.
#[ORM\DiscriminatorMap([TriggersEntities\Notifications\Email::TYPE => TriggersEntities\Notifications\Email::class])]
#[ORM\MappedSuperclass]
abstract class Notification implements TriggersEntities\Entity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use TriggersEntities\TEntity;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'notification_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'notification_enabled',
		type: 'boolean',
		length: 1,
		nullable: false,
		options: ['default' => true],
	)]
	protected bool $enabled = true;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: TriggersEntities\Triggers\Trigger::class,
		inversedBy: 'notifications',
	)]
	#[ORM\JoinColumn(
		name: 'trigger_id',
		referencedColumnName: 'trigger_id',
		onDelete: 'CASCADE',
	)]
	protected TriggersEntities\Triggers\Trigger|null $trigger;

	public function __construct(
		TriggersEntities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->trigger = $trigger;
	}

	abstract public static function getType(): string;

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function getTrigger(): TriggersEntities\Triggers\Trigger
	{
		assert($this->trigger instanceof TriggersEntities\Triggers\Trigger);

		return $this->trigger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => static::getType(),
			'enabled' => $this->isEnabled(),

			'trigger' => $this->getTrigger()->getPlainId(),

			'owner' => $this->getTrigger()->getOwnerId(),
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
