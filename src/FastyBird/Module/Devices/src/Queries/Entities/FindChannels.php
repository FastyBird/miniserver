<?php declare(strict_types = 1);

/**
 * FindChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           30.07.18
 */

namespace FastyBird\Module\Devices\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Devices\Entities;
use Ramsey\Uuid;
use SortDirection;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Channel
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannels extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('ch.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('ch.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('ch.identifier LIKE :identifier')->setParameter('identifier', $identifier . '%');
		};
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('ch.identifier LIKE :identifier')->setParameter('identifier', '%' . $identifier);
		};
	}

	public function forDevice(Entities\Devices\Device $device): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('device.id = :device')
				->setParameter('device', $device->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($deviceId): void {
			$qb->andWhere('device.id = :deviceId')
				->setParameter('deviceId', $deviceId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byDeviceIdentifier(string $deviceIdentifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($deviceIdentifier): void {
			$qb->andWhere('device.identifier = :deviceIdentifier')
				->setParameter('deviceIdentifier', $deviceIdentifier);
		};
	}

	public function withProperties(): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(ch.properties) <> 0');
		};
	}

	public function withSettableProperties(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('ch.properties', 'properties');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('properties.settable = :settable')->setParameter('settable', true);
		};
	}

	public function sortBy(
		string $sortBy,
		SortDirection $sortDir = SortDirection::Ascending,
	): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($sortBy, $sortDir): void {
			$qb->addOrderBy($sortBy, $sortDir);
		};
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository);

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('ch');
		$qb->addSelect('device');
		$qb->join('ch.device', 'device');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<T> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(ch.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
