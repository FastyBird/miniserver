<?php declare(strict_types = 1);

/**
 * FindChannelProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           25.11.18
 */

namespace FastyBird\Module\Devices\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Devices\Entities;
use Ramsey\Uuid;
use SortDirection;

/**
 * Find channel properties entities query
 *
 * @template T of Entities\Channels\Properties\Property
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannelProperties extends DoctrineOrmQuery\QueryObject
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

	public function forChannel(Entities\Channels\Channel $channel): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('channel.id = :channel')
				->setParameter('channel', $channel->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byChannelId(Uuid\UuidInterface $channelId): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channelId): void {
			$qb->andWhere('channel.id = :channel')
				->setParameter('channel', $channelId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forParent(Entities\Channels\Properties\Property $parent): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($parent): void {
			$qb->andWhere('p.parent = :parent')
				->setParameter('parent', $parent->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
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
		$qb = $repository->createQueryBuilder('p');
		$qb->addSelect('channel');
		$qb->join('p.channel', 'channel');

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
