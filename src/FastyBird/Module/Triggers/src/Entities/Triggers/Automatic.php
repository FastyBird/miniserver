<?php declare(strict_types = 1);

/**
 * Automatic.php
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
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Module\Triggers\Entities;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_triggers_automatic',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Automatic triggers',
	],
)]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Automatic extends Trigger
{

	public const TYPE = 'automatic';

	/** @var Common\Collections\Collection<int, Entities\Conditions\Condition> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'trigger',
		targetEntity: Entities\Conditions\Condition::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	private Common\Collections\Collection $conditions;

	public function __construct(string $name, Uuid\UuidInterface|null $id = null)
	{
		parent::__construct($name, $id);

		$this->conditions = new Common\Collections\ArrayCollection();
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @return array<Entities\Conditions\Condition>
	 */
	public function getConditions(): array
	{
		return $this->conditions->toArray();
	}

	/**
	 * @param array<Entities\Conditions\Condition> $conditions
	 */
	public function setConditions(array $conditions = []): void
	{
		$this->conditions = new Common\Collections\ArrayCollection();

		foreach ($conditions as $entity) {
			$this->addCondition($entity);
		}
	}

	public function addCondition(Entities\Conditions\Condition $condition): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->conditions->contains($condition)) {
			// ...and assign it to collection
			$this->conditions->add($condition);
		}
	}

	public function getCondition(string $id): Entities\Conditions\Condition|null
	{
		$found = $this->conditions
			->filter(static fn (Entities\Conditions\Condition $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeCondition(Entities\Conditions\Condition $condition): void
	{
		// Check if collection contain removing entity...
		if ($this->conditions->contains($condition)) {
			// ...and remove it from collection
			$this->conditions->removeElement($condition);
		}
	}

}
