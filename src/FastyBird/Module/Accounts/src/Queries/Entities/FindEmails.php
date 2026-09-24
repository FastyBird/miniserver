<?php declare(strict_types = 1);

/**
 * FindEmails.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Accounts\Entities;
use Ramsey\Uuid;

/**
 * Find accounts entities query
 *
 * @extends  Query\QueryObject<Entities\Emails\Email>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindEmails extends Query\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('e.id = :id')
				->setParameter('id', $id->getBytes());
		};
	}

	public function byAddress(string $address): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($address): void {
			$qb->andWhere('e.address = :address')
				->setParameter('address', $address);
		};
	}

	public function forAccount(Entities\Accounts\Account $account): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('e.account', 'account');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($account): void {
			$qb->andWhere('account.id = :account')
				->setParameter('account', $account->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param ORM\EntityRepository<Entities\Emails\Email> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository);
	}

	/**
	 * @param ORM\EntityRepository<Entities\Emails\Email> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('e');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<Entities\Emails\Email> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository)
			->select('COUNT(e.id)');
	}

}
