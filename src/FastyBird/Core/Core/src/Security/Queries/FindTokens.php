<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Persistence\Query;
use FastyBird\Core\Security\Entities\Tokens;
use FastyBird\Core\Security\Types;
use Override;
use Ramsey\Uuid;
use SensitiveParameterValue;

/**
 * Find tokens entities query
 *
 * @template T of Tokens\Token
 * @extends  Query\QueryObject<T>
 */
final class FindTokens extends Query\QueryObject
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
		// The filter keeps the token wrapped: this query is an argument of the frames that run it,
		// and a dump of them would otherwise print the token the closure captures
		$token = new SensitiveParameterValue($token);

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($token): void {
			$qb->andWhere('t.token = :token')->setParameter('token', $token->getValue());
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
	#[Override]
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
	#[Override]
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository)->select('COUNT(t.id)');
	}

}
