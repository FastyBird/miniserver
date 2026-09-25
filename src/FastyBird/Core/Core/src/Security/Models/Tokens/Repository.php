<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\Tokens;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Query;
use FastyBird\Core\Security\Entities\Tokens;
use FastyBird\Core\Security\Queries;
use FastyBird\Core\Security\Types;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_array;

/**
 * Security token repository
 */
final class Repository
{

	/** @var array<ORM\EntityRepository<Tokens\Token>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @template T of Tokens\Token
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function findOneByIdentifier(
		string $identifier,
		string $type = Tokens\Token::class,
	): Tokens\Token|null
	{
		$findQuery = new Queries\FindTokens();
		$findQuery->byId(Uuid\Uuid::fromString($identifier));
		$findQuery->inState(Types\TokenState::ACTIVE);

		$result = $this->findOneBy($findQuery, $type);
		assert($result instanceof $type || $result === null);

		return $result;
	}

	/**
	 * @template T of Tokens\Token
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneByToken(
		string $token,
		string $type = Tokens\Token::class,
	): Tokens\Token|null
	{
		$findQuery = new Queries\FindTokens();
		$findQuery->byToken($token);
		$findQuery->inState(Types\TokenState::ACTIVE);

		$result = $this->findOneBy($findQuery, $type);
		assert($result instanceof $type || $result === null);

		return $result;
	}

	/**
	 * @template T of Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneBy(
		Queries\FindTokens $queryObject,
		string $type = Tokens\Token::class,
	): Tokens\Token|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @template T of Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindTokens $queryObject,
		string $type = Tokens\Token::class,
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
	 * @template T of Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return Query\ResultSet<T>
	 *
	 * @throws PersistenceExceptions\Query
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindTokens $queryObject,
		string $type = Tokens\Token::class,
	): Query\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			throw new CoreExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Tokens\Token
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
