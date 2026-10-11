<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Queries\Entities;

use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\Core\Security\Queries;
use FastyBird\Module\Accounts\Entities;
use Ramsey\Uuid;

/**
 * Find roles entities query
 *
 * @extends  Queries\FindPolicies<Entities\Roles\Role>
 */
final class FindRoles extends Queries\FindPolicies
{

	public function forParent(Entities\Roles\Role $role): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('p.parent', 'parent');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($role): void {
			$qb->andWhere('parent.id = :parent')
				->setParameter('parent', $role->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byName(string $name): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($name): void {
			$qb->andWhere('p.v0 = :name')->setParameter('name', $name);
		};
	}

	/**
	 * @param array<string> $names
	 */
	public function byNames(array $names): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($names): void {
			$qb->andWhere('p.v0 IN (:names)')->setParameter('names', $names, DBAL\ArrayParameterType::STRING);
		};
	}

}
