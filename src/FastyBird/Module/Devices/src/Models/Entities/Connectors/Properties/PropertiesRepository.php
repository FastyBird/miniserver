<?php declare(strict_types = 1);

/**
 * PropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Models\Entities\Connectors\Properties;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Queries;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function is_array;

/**
 * Connector channel property structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertiesRepository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Connectors\Properties\Property>> */
	private array $repository = [];

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @template T of Entities\Connectors\Properties\Property
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Entities\Connectors\Properties\Property::class,
	): Entities\Connectors\Properties\Property|null
	{
		return $this->database->query(
			fn (): Entities\Connectors\Properties\Property|null => $this->getRepository($type)->find($id),
		);
	}

	/**
	 * @template T of Entities\Connectors\Properties\Property
	 *
	 * @param Queries\Entities\FindConnectorProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindConnectorProperties $queryObject,
		string $type = Entities\Connectors\Properties\Property::class,
	): Entities\Connectors\Properties\Property|null
	{
		return $this->database->query(
			fn (): Entities\Connectors\Properties\Property|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @template T of Entities\Connectors\Properties\Property
	 *
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function findAll(string $type = Entities\Connectors\Properties\Property::class): array
	{
		return $this->database->query(
			fn (): array => $this->getRepository($type)->findAll(),
		);
	}

	/**
	 * @template T of Entities\Connectors\Properties\Property
	 *
	 * @param Queries\Entities\FindConnectorProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindConnectorProperties $queryObject,
		string $type = Entities\Connectors\Properties\Property::class,
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
	 * @template T of Entities\Connectors\Properties\Property
	 *
	 * @param Queries\Entities\FindConnectorProperties<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return DoctrineOrmQuery\ResultSet<T>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindConnectorProperties $queryObject,
		string $type = Entities\Connectors\Properties\Property::class,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $this->database->query(
			fn (): DoctrineOrmQuery\ResultSet|array => $queryObject->fetch($this->getRepository($type)),
		);

		if (is_array($result)) {
			throw new Exceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Entities\Connectors\Properties\Property
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
