<?php declare(strict_types = 1);

/**
 * EmailsRepository.php
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

namespace FastyBird\Module\Accounts\Models\Entities\Emails;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Queries;
use Nette;
use Throwable;
use function is_array;

/**
 * Account email address repository
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Emails\Email>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneByAddress(string $address): Entities\Emails\Email|null
	{
		$findQuery = new Queries\Entities\FindEmails();
		$findQuery->byAddress($address);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindEmails $queryObject,
	): Entities\Emails\Email|null
	{
		return $this->database->query(
			fn (): Entities\Emails\Email|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @return array<Entities\Emails\Email>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\Entities\FindEmails $queryObject): array
	{
		try {
			/** @var array<Entities\Emails\Email> $result */
			$result = $this->getResultSet($queryObject)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return Query\ResultSet<Entities\Emails\Email>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindEmails $queryObject,
	): Query\ResultSet
	{
		$result = $this->database->query(
			fn (): Query\ResultSet|array => $queryObject->fetch($this->getRepository()),
		);

		if (is_array($result)) {
			throw new Exceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @param class-string<Entities\Emails\Email> $type
	 *
	 * @return ORM\EntityRepository<Entities\Emails\Email>
	 */
	private function getRepository(string $type = Entities\Emails\Email::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
