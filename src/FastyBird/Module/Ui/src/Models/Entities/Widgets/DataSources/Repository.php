<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Models\Entities\Widgets\DataSources;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Queries;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function is_array;

/**
 * Widget data source repository
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Widgets\DataSources\DataSource>> */
	private array $repository = [];

	public function __construct(
		private readonly Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
	 *
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $id,
		string $type = Entities\Widgets\DataSources\DataSource::class,
	): Entities\Widgets\DataSources\DataSource|null
	{
		return $this->database->query(
			fn (): Entities\Widgets\DataSources\DataSource|null => $this->getRepository($type)->find($id),
		);
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
	 *
	 * @param Queries\Entities\FindWidgetDataSources<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return T|null
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindWidgetDataSources $queryObject,
		string $type = Entities\Widgets\DataSources\DataSource::class,
	): Entities\Widgets\DataSources\DataSource|null
	{
		return $this->database->query(
			fn (): Entities\Widgets\DataSources\DataSource|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
	 *
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function findAll(string $type = Entities\Widgets\DataSources\DataSource::class): array
	{
		return $this->database->query(
			fn (): array => $this->getRepository($type)->findAll(),
		);
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
	 *
	 * @param Queries\Entities\FindWidgetDataSources<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return array<T>
	 *
	 * @throws UiExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindWidgetDataSources $queryObject,
		string $type = Entities\Widgets\DataSources\DataSource::class,
	): array
	{
		try {
			/** @var array<T> $result */
			$result = $this->getResultSet($queryObject, $type)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new UiExceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
	 *
	 * @param Queries\Entities\FindWidgetDataSources<T> $queryObject
	 * @param class-string<T> $type
	 *
	 * @return Query\ResultSet<T>
	 *
	 * @throws UiExceptions\InvalidState
	 * @throws CoreExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindWidgetDataSources $queryObject,
		string $type = Entities\Widgets\DataSources\DataSource::class,
	): Query\ResultSet
	{
		$result = $this->database->query(
			fn (): Query\ResultSet|array => $queryObject->fetch($this->getRepository($type)),
		);

		if (is_array($result)) {
			throw new UiExceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @template T of Entities\Widgets\DataSources\DataSource
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
