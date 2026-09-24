<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\SimpleAuth\Models\Policies;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\SimpleAuth as Entities;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Query;
use FastyBird\Core\Persistence\SimpleAuth\Queries;
use Throwable;
use function is_array;

/**
 * Security token repository
 */
final class Repository
{

	/** @var array<ORM\EntityRepository<Entities\Policies\Policy>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @template T of Entities\Policies\Policy
	 *
	 * @param Queries\FindPolicies<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneBy(
		Queries\FindPolicies $queryObject,
		string $type = Entities\Policies\Policy::class,
	): Entities\Policies\Policy|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @template T of Entities\Policies\Policy
	 *
	 * @param Queries\FindPolicies<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindPolicies $queryObject,
		string $type = Entities\Policies\Policy::class,
	): array
	{
		try {
			/** @var array<T> $result */
			$result = $this->getResultSet($queryObject, $type)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new CoreExceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @template T of Entities\Policies\Policy
	 *
	 * @param Queries\FindPolicies<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return Query\ResultSet<T>
	 *
	 * @throws PersistenceExceptions\Query
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindPolicies $queryObject,
		string $type = Entities\Policies\Policy::class,
	): Query\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			throw new CoreExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Entities\Policies\Policy
	 *
	 * @param class-string<T> $type
	 *
	 * @return ORM\EntityRepository<T>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		/** @var ORM\EntityRepository<T> $repository */
		$repository = $this->repository[$type];

		return $repository;
	}

}
