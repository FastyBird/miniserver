<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\SimpleAuth\Models\Tokens;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\SimpleAuth as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Core\Persistence\SimpleAuth\Queries;
use FastyBird\Core\Types\SimpleAuth as Types;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_array;

/**
 * Security token repository
 */
final class Repository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Tokens\Token>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @template T of Entities\Tokens\Token
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	public function findOneByIdentifier(
		string $identifier,
		string $type = Entities\Tokens\Token::class,
	): Entities\Tokens\Token|null
	{
		$findQuery = new Queries\FindTokens();
		$findQuery->byId(Uuid\Uuid::fromString($identifier));
		$findQuery->inState(Types\TokenState::ACTIVE);

		$result = $this->findOneBy($findQuery, $type);
		assert($result instanceof $type || $result === null);

		return $result;
	}

	/**
	 * @template T of Entities\Tokens\Token
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	public function findOneByToken(
		string $token,
		string $type = Entities\Tokens\Token::class,
	): Entities\Tokens\Token|null
	{
		$findQuery = new Queries\FindTokens();
		$findQuery->byToken($token);
		$findQuery->inState(Types\TokenState::ACTIVE);

		$result = $this->findOneBy($findQuery, $type);
		assert($result instanceof $type || $result === null);

		return $result;
	}

	/**
	 * @template T of Entities\Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	public function findOneBy(
		Queries\FindTokens $queryObject,
		string $type = Entities\Tokens\Token::class,
	): Entities\Tokens\Token|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @template T of Entities\Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindTokens $queryObject,
		string $type = Entities\Tokens\Token::class,
	): array
	{
		try {
			/** @var array<T> $result */
			$result = $this->getResultSet($queryObject, $type)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @template T of Entities\Tokens\Token
	 *
	 * @param Queries\FindTokens<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return DoctrineOrmQuery\ResultSet<T>
	 *
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindTokens $queryObject,
		string $type = Entities\Tokens\Token::class,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			throw new Exceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Entities\Tokens\Token
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
