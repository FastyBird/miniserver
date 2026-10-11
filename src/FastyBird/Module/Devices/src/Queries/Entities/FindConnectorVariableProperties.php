<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Devices\Entities;

/**
 * Find connector variable properties entities query
 *
 * @template T of Entities\Connectors\Properties\Variable
 * @extends  FindConnectorProperties<T>
 */
final class FindConnectorVariableProperties extends FindConnectorProperties
{

	public function byValue(string $value): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('p.value = :value')->setParameter('value', $value);
		};
	}

}
