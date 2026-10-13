<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\ApiKeys;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Query;
use FastyBird\Core\Security\Entities\ApiKeys;
use FastyBird\Core\Security\Queries;
use Ramsey\Uuid;
use SensitiveParameter;
use Throwable;
use function is_array;

/**
 * Security API keys repository
 */
final class Repository
{

	/** @var ORM\EntityRepository<ApiKeys\ApiKey>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * Finds a key in any state
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneById(Uuid\UuidInterface $id): ApiKeys\ApiKey|null
	{
		/** @var Queries\FindApiKeys<ApiKeys\ApiKey> $findQuery */
		$findQuery = new Queries\FindApiKeys();
		$findQuery->byId($id);

		return $this->findOneBy($findQuery);
	}

	/**
	 * Finds a key in any state by the hash of its secret. The caller decides whether that state
	 * and the expiry allow the key to authenticate.
	 *
	 * The key is always read from the database. A query does not overwrite an entity the identity
	 * map already holds, so in a long-running process that kept the key from an earlier request,
	 * a suspension, deletion or new expiry written by another process (the fb:api-key:* commands)
	 * would otherwise go unseen until the entity manager is cleared.
	 *
	 * @param non-empty-string $hash
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneByHash(#[SensitiveParameter] string $hash): ApiKeys\ApiKey|null
	{
		/** @var Queries\FindApiKeys<ApiKeys\ApiKey> $findQuery */
		$findQuery = new Queries\FindApiKeys();
		$findQuery->byHash($hash);

		$apiKey = $this->findOneBy($findQuery);

		if ($apiKey !== null) {
			$this->managerRegistry->getManagerForClass(ApiKeys\ApiKey::class)?->refresh($apiKey);
		}

		return $apiKey;
	}

	/**
	 * @param Queries\FindApiKeys<ApiKeys\ApiKey> $queryObject
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 */
	public function findOneBy(Queries\FindApiKeys $queryObject): ApiKeys\ApiKey|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @param Queries\FindApiKeys<ApiKeys\ApiKey> $queryObject
	 *
	 * @return array<ApiKeys\ApiKey>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findAllBy(Queries\FindApiKeys $queryObject): array
	{
		try {
			/** @var array<ApiKeys\ApiKey> $result */
			$result = $this->getResultSet($queryObject)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new CoreExceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param Queries\FindApiKeys<ApiKeys\ApiKey> $queryObject
	 *
	 * @return Query\ResultSet<ApiKeys\ApiKey>
	 *
	 * @throws PersistenceExceptions\Query
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(Queries\FindApiKeys $queryObject): Query\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			throw new CoreExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @return ORM\EntityRepository<ApiKeys\ApiKey>
	 */
	private function getRepository(): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository(ApiKeys\ApiKey::class);
		}

		return $this->repository;
	}

}
