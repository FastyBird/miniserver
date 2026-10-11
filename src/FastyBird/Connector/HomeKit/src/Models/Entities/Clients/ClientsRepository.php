<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Models\Entities\Clients;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Entities\Clients\Client;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Query;
use function is_array;

/**
 * Clients repository
 */
final class ClientsRepository
{

	/** @var ORM\EntityRepository<Client>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param Queries\Entities\FindClients<Client> $queryObject
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindClients $queryObject,
	): Entities\Clients\Client|null
	{
		return $this->database->query(
			fn (): Entities\Clients\Client|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @param Queries\Entities\FindClients<Client> $queryObject
	 *
	 * @return Query\ResultSet<Client>
	 *
	 * @throws HomeKitExceptions\InvalidState
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindClients $queryObject,
	): Query\ResultSet
	{
		$result = $this->database->query(
			fn (): Query\ResultSet|array => $queryObject->fetch($this->getRepository()),
		);

		if (is_array($result)) {
			throw new HomeKitExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @return ORM\EntityRepository<Client>
	 */
	private function getRepository(): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository(Entities\Clients\Client::class);
		}

		return $this->repository;
	}

}
