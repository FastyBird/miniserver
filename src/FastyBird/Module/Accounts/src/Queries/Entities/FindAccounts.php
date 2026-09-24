<?php declare(strict_types = 1);

/**
 * FindAccounts.php
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
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Types;
use Ramsey\Uuid;
use function in_array;

/**
 * Find accounts entities query
 *
 * @extends  Query\QueryObject<Entities\Accounts\Account>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindAccounts extends Query\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('a.id = :id')
				->setParameter('id', $id->getBytes());
		};
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function inState(Types\AccountState $state): void
	{
		if (!in_array($state, Types\AccountState::getAllowed(), true)) {
			throw new Exceptions\InvalidArgument('Invalid account state given');
		}

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($state): void {
			$qb->andWhere('a.state = :state')
				->setParameter('state', $state->value);
		};
	}

	public function inRole(Entities\Roles\Role $role): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('a.roles', 'roles');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($role): void {
			$qb->andWhere('roles.id = :role')
				->setParameter('role', $role->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param ORM\EntityRepository<Entities\Accounts\Account> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository);
	}

	/**
	 * @param ORM\EntityRepository<Entities\Accounts\Account> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('a');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository<Entities\Accounts\Account> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository)
			->select('COUNT(a.id)');
	}

}
