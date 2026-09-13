<?php declare(strict_types = 1);

/**
 * FindPolicies.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           15.07.24
 */

namespace FastyBird\Core\SimpleAuth\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\SimpleAuth\Entities;
use FastyBird\Core\SimpleAuth\Types;
use FastyBird\Library\DoctrineOrmQuery;
use Ramsey\Uuid;
use function assert;

/**
 * Find tokens entities query
 *
 * @template T of Entities\Policies\Policy
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package          FastyBird:SimpleAuth!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindPolicies extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('p.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byType(Types\PolicyType $type): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($type): void {
			$qb->andWhere('p.type = :type')->setParameter('type', $type);
		};
	}

	public function byValue(int $key, string|null $value): void
	{
		assert($key >= 0 && $key <= 5);

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($key, $value): void {
			$qb->andWhere('p.v' . $key . ' = :v' . $key . 'value')->setParameter('v' . $key . 'value', $value);
		};
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository);
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('p');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

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
		return $this->createBasicDql($repository)->select('COUNT(p.id)');
	}

}
