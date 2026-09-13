<?php declare(strict_types = 1);

/**
 * FindDevices.php
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
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Devices\Entities;
use Ramsey\Uuid;

/**
 * Find devices entities query
 *
 * @template T of Entities\Devices\Device
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('d.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('d.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('d.identifier LIKE :identifier')->setParameter('identifier', $identifier . '%');
		};
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('d.identifier LIKE :identifier')->setParameter('identifier', '%' . $identifier);
		};
	}

	public function forConnector(Entities\Connectors\Connector $connector): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->addSelect('connector');
			$qb->join('d.connector', 'connector');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($connector): void {
			$qb->andWhere('connector.id = :connector')
				->setParameter('connector', $connector->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->addSelect('connector');
			$qb->join('d.connector', 'connector');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($connectorId): void {
			$qb->andWhere('connector.id = :connector')
				->setParameter('connector', $connectorId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forParent(Entities\Devices\Device $parent): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->innerJoin('d.parents', 'dp');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($parent): void {
			$qb->andWhere('dp.id = :parentDevice')
				->setParameter('parentDevice', $parent->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forChild(Entities\Devices\Device $child): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->innerJoin('d.children', 'dch');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($child): void {
			$qb->andWhere('dch.id = :childDevice')
				->setParameter('childDevice', $child->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function withSettableChannelProperties(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.channels', 'channels');
			$qb->join('channels.properties', 'chProperties');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('chProperties.settable = :settable')->setParameter('settable', true);
		};
	}

	public function withChannels(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.channels', 'channels');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(channels.children) = 0');
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
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('d');

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
		$qb = $this->createBasicDql($repository)->select('COUNT(d.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
