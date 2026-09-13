<?php declare(strict_types = 1);

/**
 * FindDeviceProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Module\Devices\Queries\Entities;

use Closure;
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Devices\Entities;
use Ramsey\Uuid;

/**
 * Find device properties entities query
 *
 * @template T of Entities\Devices\Properties\Property
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceProperties extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('p.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('p.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('p.identifier LIKE :identifier')->setParameter('identifier', $identifier . '%');
		};
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('p.identifier LIKE :identifier')->setParameter('identifier', '%' . $identifier);
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
			$qb->andWhere('device.id = :device')
				->setParameter('device', $deviceId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forParent(Entities\Devices\Properties\Property $property): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('p.parent = :parent')
				->setParameter('parent', $property->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($parentId): void {
			$qb->andWhere('p.parent = :parent')->setParameter('parent', $parentId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function settable(bool $state): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($state): void {
			$qb->andWhere('p.settable = :settable')->setParameter('settable', $state);
		};
	}

	public function queryable(bool $state): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($state): void {
			$qb->andWhere('p.queryable = :queryable')->setParameter('queryable', $state);
		};
	}

	public function sortBy(
		string $sortBy,
		Common\Collections\Order $sortDir = Common\Collections\Order::Ascending,
	): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($sortBy, $sortDir): void {
			$qb->addOrderBy($sortBy, $sortDir->value);
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
		$qb = $repository->createQueryBuilder('p');
		$qb->addSelect('device');
		$qb->join('p.device', 'device');

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
		$qb = $this->createBasicDql($repository)->select('COUNT(p.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
