<?php declare(strict_types = 1);

/**
 * Action.php
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

namespace FastyBird\Module\Triggers\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_actions',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Triggers actions',
	],
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'action_type', type: 'string', length: 100)]
// Seeded with this root itself, which is exactly what Core\Application's EntityDiscriminator
// appends when the root is absent from its own map; the concrete subtypes are contributed at
// runtime from #[DiscriminatorEntry] in the automator packages that own them.
//
// The map must be non-empty. ClassMetadataFactory calls addDefaultDiscriminatorMap() before
// dispatching loadClassMetadata and only when the map is empty, and that default keys every
// subtype by its short class name -- so each subtype would land in the map twice, once under
// the short name and once under its DiscriminatorEntry name. An explicit map skips the default
// entirely, which is what the removed doctrine/orm patch achieved by deferring the call.
#[ORM\DiscriminatorMap(['action' => Action::class])]
#[ORM\MappedSuperclass]
abstract class Action implements TriggersEntities\Entity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use TriggersEntities\TEntity;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'action_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'action_enabled', type: 'boolean', length: 1, nullable: false, options: ['default' => true])]
	protected bool $enabled = true;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: TriggersEntities\Triggers\Trigger::class,
		inversedBy: 'actions',
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
