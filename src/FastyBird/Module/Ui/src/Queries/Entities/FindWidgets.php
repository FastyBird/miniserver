<?php declare(strict_types = 1);

/**
 * FindWidgets.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Ui\Entities;
use Ramsey\Uuid;

/**
 * Find widgets entities query
 *
 * @template  T of Entities\Widgets\Widget
 * @extends   Query\QueryObject<T>
 *
 * @package          FastyBird:UIModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindWidgets extends Query\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	protected array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('w.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function inDashboard(Entities\Dashboards\Dashboard $dashboard): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('w.tabs', 't');
			$qb->join('t.dashboards', 'd');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($dashboard): void {
			$qb->andWhere('d.id = :dashboard')->setParameter(
				'dashboard',
				$dashboard->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

	public function inTab(Entities\Dashboards\Tabs\Tab $tab): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('w.tabs', 't');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($tab): void {
			$qb->andWhere('t.id = :tab')->setParameter(
				'tab',
				$tab->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

	public function inGroup(Entities\Groups\Group $group): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('w.groups', 'g');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($group): void {
			$qb->andWhere('g.id = :group')->setParameter(
				'group',
				$group->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

	public function withDataSource(Entities\Widgets\DataSources\DataSource $dataSource): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->addSelect('dataSources');
			$qb->leftJoin('w.dataSources', 'dataSources');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($dataSource): void {
			$qb->andWhere('dataSources.id = :dataSource')->setParameter(
				'dataSource',
				$dataSource->getId(),
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
		$qb = $repository->createQueryBuilder('w');
		$qb->addSelect('display');
		$qb->join('w.display', 'display');

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
		$qb = $this->createBasicDql($repository)->select('COUNT(w.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
