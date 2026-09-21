<?php declare(strict_types = 1);

/**
 * FindConditions.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Module\Triggers\Entities;
use Ramsey\Uuid;

/**
 * Find conditions entities query
 *
 * @template T of Entities\Conditions\Condition
 * @extends DoctrineOrmQuery\QueryObject<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConditions extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('c.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forTrigger(Entities\Triggers\Trigger $trigger): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($trigger): void {
			$qb->andWhere('trigger.id = :trigger')
				->setParameter('trigger', $trigger->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function onlyEnabledTriggers(): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('trigger.enabled = :enabled')->setParameter('enabled', true);
		};
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository);

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('c');
		$qb->addSelect('trigger');
		$qb->join('c.trigger', 'trigger');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(c.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
