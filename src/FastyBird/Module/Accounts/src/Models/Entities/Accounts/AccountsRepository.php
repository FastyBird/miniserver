<?php declare(strict_types = 1);

/**
 * AccountsRepository.php
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

namespace FastyBird\Module\Accounts\Models\Entities\Accounts;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Queries;
use Nette;
use Throwable;
use function is_array;

/**
 * Account repository
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Accounts\Account>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindAccounts $queryObject,
	): Entities\Accounts\Account|null
	{
		return $this->database->query(
			fn (): Entities\Accounts\Account|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @return array<Entities\Accounts\Account>
	 *
	 * @throws AccountsExceptions\InvalidState
	 */
	public function findAllBy(Queries\Entities\FindAccounts $queryObject): array
	{
		try {
			/** @var array<Entities\Accounts\Account> $result */
			$result = $this->getResultSet($queryObject)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new AccountsExceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return Query\ResultSet<Entities\Accounts\Account>
	 *
	 * @throws AccountsExceptions\InvalidState
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindAccounts $queryObject,
	): Query\ResultSet
	{
		$result = $this->database->query(
			fn (): Query\ResultSet|array => $queryObject->fetch($this->getRepository()),
		);

		if (is_array($result)) {
			throw new AccountsExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @param class-string<Entities\Accounts\Account> $type
	 *
	 * @return ORM\EntityRepository<Entities\Accounts\Account>
	 */
	private function getRepository(string $type = Entities\Accounts\Account::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
