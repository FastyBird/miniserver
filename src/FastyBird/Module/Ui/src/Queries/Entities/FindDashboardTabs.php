<?php declare(strict_types = 1);

/**
 * FindDashboardTabs.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Ui\Entities;
use Ramsey\Uuid;

/**
 * Find dashboard tab entities query
 *
 * @template T of Entities\Dashboards\Tabs\Tab
 * @extends  DoctrineOrmQuery\QueryObject<T>
 *
 * @package          FastyBird:UIModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDashboardTabs extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('t.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forDashboard(Entities\Dashboards\Dashboard $dashboard): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('t.dashboard', 'dashboard');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($dashboard): void {
			$qb->andWhere('dashboard.id = :dashboard')->setParameter(
				'dashboard',
				$dashboard->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

	public function forWidget(Entities\Widgets\Widget $widget): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('t.widgets', 'widget');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($widget): void {
			$qb->andWhere('widget.id = :widget')->setParameter(
				'widget',
				$widget->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
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
		$qb = $repository->createQueryBuilder('t');

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
		$qb = $this->createBasicDql($repository)->select('COUNT(t.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
