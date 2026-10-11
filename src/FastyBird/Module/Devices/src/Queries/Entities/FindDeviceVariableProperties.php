<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Devices\Entities;

/**
 * Find device variable properties entities query
 *
 * @template T of Entities\Devices\Properties\Variable
 * @extends  FindDeviceProperties<T>
 */
class FindDeviceVariableProperties extends FindDeviceProperties
{

	public function byValue(string $value): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('p.value = :value')->setParameter('value', $value);
		};
	}

}
