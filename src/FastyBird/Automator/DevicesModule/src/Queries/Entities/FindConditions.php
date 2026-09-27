<?php declare(strict_types = 1);

/**
 * FindConditions.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Automator\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Queries as TriggersQueries;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use Ramsey\Uuid;

/**
 * Find conditions entities query
 *
 * @template T of TriggersEntities\Conditions\Condition
 * @extends TriggersQueries\Entities\FindConditions<T>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConditions extends TriggersQueries\Entities\FindConditions
{

	public function forDevice(Uuid\UuidInterface $device): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('cdc.device = :device')->setParameter('device', $device, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forChannel(Uuid\UuidInterface $channel): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('cdc.channel = :channel')
				->setParameter('channel', $channel, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forProperty(Uuid\UuidInterface $property): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('cdc.property = :property')
				->setParameter('property', $property, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function withPropertyValue(
		string $value,
		TriggersTypes\ConditionOperator $operator = TriggersTypes\ConditionOperator::EQUAL,
	): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($operator): void {
			$qb->andWhere('cdc.operator = :operator')->setParameter('operator', $operator->value);
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('cdc.operand = :operand')->setParameter('operand', $value);
		};
	}

	public function byValue(float $value, float|null $previousValue = null): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			if ($previousValue !== null) {
				$qb
					->andWhere(
						'(previousValue <= cdc.operand AND cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(previousValue >= cdc.operand AND cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(previousValue <> cdc.operand AND cdc.operand = :value AND cdc.operator = :operatorEqual)',
					)
					->setParameter('value', $value)
					->setParameter('previousValue', $previousValue)
					->setParameter('operatorAbove', TriggersTypes\ConditionOperator::ABOVE->value)
					->setParameter('operatorBelow', TriggersTypes\ConditionOperator::BELOW->value)
					->setParameter('operatorEqual', TriggersTypes\ConditionOperator::EQUAL->value);

			} else {
				$qb
					->andWhere(
						'(cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(cdc.operand = :value AND cdc.operator = :operatorEqual)',
					)
					->setParameter('value', $value)
					->setParameter('operatorAbove', TriggersTypes\ConditionOperator::ABOVE->value)
					->setParameter('operatorBelow', TriggersTypes\ConditionOperator::BELOW->value)
					->setParameter('operatorEqual', TriggersTypes\ConditionOperator::EQUAL->value);
			}
		};
	}

	public function byValueAbove(float $value, float $previousValue): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand >= :previousValue AND cdc.operand < :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', TriggersTypes\ConditionOperator::ABOVE->value);
		};
	}

	public function byValueBelow(float $value, float $previousValue): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand <= :previousValue AND cdc.operand > :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', TriggersTypes\ConditionOperator::BELOW->value);
		};
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		if ($repository->getClassName() === DevicesModuleEntities\Conditions\PropertyCondition::class) {
			$qb = $repository->createQueryBuilder('pc');
			$qb->join(TriggersEntities\Conditions\Condition::class, 'c', ORM\Query\Expr\Join::WITH, 'pc = c');

		} elseif (
			$repository->getClassName() === DevicesModuleEntities\Conditions\ChannelPropertyCondition::class
			|| $repository->getClassName() === DevicesModuleEntities\Conditions\DevicePropertyCondition::class
		) {
			$qb = $repository->createQueryBuilder('cdc');
			$qb->join(TriggersEntities\Conditions\Condition::class, 'c', ORM\Query\Expr\Join::WITH, 'cdc = c');
			$qb->join('c.trigger', 'trigger');

		} else {
			$qb = $repository->createQueryBuilder('c');
			$qb->addSelect('trigger');
			$qb->join('c.trigger', 'trigger');
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
