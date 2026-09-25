<?php declare(strict_types = 1);

/**
 * DevicesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           28.07.18
 */

namespace FastyBird\Module\Devices\Models\Entities\Devices;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Queries;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function is_array;

/**
 * Device repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicesRepository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Devices\Device>> */
	private array $repository = [];

	public function __construct(
		private readonly Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Entities\Devices\Device::class,
	): Entities\Devices\Device|null
	{
		return $this->database->query(
			fn (): Entities\Devices\Device|null => $this->getRepository($type)->find($id),
		);
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @param Queries\Entities\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): Entities\Devices\Device|null
	{
		return $this->database->query(
			fn (): Entities\Devices\Device|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findAll(string $type = Entities\Devices\Device::class): array
	{
		return $this->database->query(
			fn (): array => $this->getRepository($type)->findAll(),
		);
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @param Queries\Entities\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): array
	{
		try {
			/** @var array<T> $result */
			$result = $this->getResultSet($queryObject, $type)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new DevicesExceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @param Queries\Entities\FindDevices<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return Query\ResultSet<T>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): Query\ResultSet
	{
		$result = $this->database->query(
			fn (): Query\ResultSet|array => $queryObject->fetch($this->getRepository($type)),
		);

		if (is_array($result)) {
			throw new DevicesExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Entities\Devices\Device
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
