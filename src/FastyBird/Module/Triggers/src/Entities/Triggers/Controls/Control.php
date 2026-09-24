<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Entities\Triggers\Controls;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_triggers_controls',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Triggers controls',
	],
)]
#[ORM\Index(columns: ['control_name'], name: 'control_name_idx')]
#[ORM\UniqueConstraint(name: 'trigger_control_unique', columns: ['control_name', 'trigger_id'])]
class Control implements TriggersEntities\Entity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use TriggersEntities\TEntity;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'control_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'control_name', type: 'string', length: 100, nullable: false)]
	private string $name;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: TriggersEntities\Triggers\Trigger::class,
		inversedBy: 'controls',
	)]
	#[ORM\JoinColumn(
		name: 'trigger_id',
		referencedColumnName: 'trigger_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private TriggersEntities\Triggers\Trigger $trigger;

	public function __construct(string $name, TriggersEntities\Triggers\Trigger $trigger)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->trigger = $trigger;

		$trigger->addControl($this);
	}

	public function getTrigger(): TriggersEntities\Triggers\Trigger
	{
		return $this->trigger;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'name' => $this->getName(),
			'trigger' => $this->getTrigger()->getPlainId(),
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
