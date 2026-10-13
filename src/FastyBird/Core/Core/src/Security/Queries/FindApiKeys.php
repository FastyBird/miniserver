<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Persistence\Query;
use FastyBird\Core\Security\Entities\ApiKeys;
use Override;
use Ramsey\Uuid;
use SensitiveParameterValue;

/**
 * Find API keys entities query
 *
 * @template T of ApiKeys\ApiKey
 * @extends  Query\QueryObject<T>
 */
final class FindApiKeys extends Query\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('k.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param non-empty-string $hash the SHA-256 hash of the secret, never the secret itself
	 */
	public function byHash(string $hash): void
	{
		// Kept wrapped, so a dump of this query object, a stack frame on a blue screen for one,
		// shows the closure's captured value hidden
		$sensitive = new SensitiveParameterValue($hash);

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($sensitive): void {
			$qb->andWhere('k.hash = :hash')->setParameter('hash', $sensitive->getValue());
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
		$qb = $repository->createQueryBuilder('k');

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
		return $this->createBasicDql($repository)->select('COUNT(k.id)');
	}

}
