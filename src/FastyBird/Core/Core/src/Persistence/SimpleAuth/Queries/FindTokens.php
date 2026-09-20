<?php declare(strict_types = 1);

/**
 * FindTokens.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Core\Persistence\SimpleAuth\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Entities\SimpleAuth as Entities;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Core\Types\SimpleAuth as Types;
use Ramsey\Uuid;

/**
 * Find tokens entities query
 *
 * @template T of Entities\Tokens\Token
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package          FastyBird:SimpleAuth!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindTokens extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('t.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byToken(string $token): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($token): void {
			$qb->andWhere('t.token = :token')->setParameter('token', $token);
		};
	}

	public function inState(Types\TokenState $state): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($state): void {
			$qb->andWhere('t.state = :state')->setParameter('state', $state->value);
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
		$qb = $repository->createQueryBuilder('t');

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
		return $this->createBasicDql($repository)->select('COUNT(t.id)');
	}

}
