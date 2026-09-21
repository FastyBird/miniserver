<?php declare(strict_types = 1);

/**
 * IdentitiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Models\Entities\Identities;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Types;
use Nette;
use Throwable;
use function is_array;

/**
 * Account identity facade
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class IdentitiesRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Identities\Identity>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneForAccount(
		Entities\Accounts\Account $account,
	): Entities\Identities\Identity|null
	{
		$findQuery = new Queries\Entities\FindIdentities();
		$findQuery->forAccount($account);
		$findQuery->inState(Types\IdentityState::ACTIVE);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneByUid(string $uid): Entities\Identities\Identity|null
	{
		$findQuery = new Queries\Entities\FindIdentities();
		$findQuery->byUid($uid);
		$findQuery->inState(Types\IdentityState::ACTIVE);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindIdentities $queryObject,
	): Entities\Identities\Identity|null
	{
		return $this->database->query(
			fn (): Entities\Identities\Identity|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @return array<Entities\Identities\Identity>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\Entities\FindIdentities $queryObject): array
	{
		try {
			/** @var array<Entities\Identities\Identity> $result */
			$result = $this->getResultSet($queryObject)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return DoctrineOrmQuery\ResultSet<Entities\Identities\Identity>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindIdentities $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $this->database->query(
			fn (): DoctrineOrmQuery\ResultSet|array => $queryObject->fetch($this->getRepository()),
		);

		if (is_array($result)) {
			throw new Exceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @param class-string<Entities\Identities\Identity> $type
	 *
	 * @return ORM\EntityRepository<Entities\Identities\Identity>
	 */
	private function getRepository(string $type = Entities\Identities\Identity::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
