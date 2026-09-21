<?php declare(strict_types = 1);

/**
 * FindRoles.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Queries\Entities;

use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\Core\Persistence\SimpleAuth\Queries as SimpleAuthQueries;
use FastyBird\Module\Accounts\Entities;
use Ramsey\Uuid;

/**
 * Find roles entities query
 *
 * @extends  SimpleAuthQueries\FindPolicies<Entities\Roles\Role>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindRoles extends SimpleAuthQueries\FindPolicies
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
