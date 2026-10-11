<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Devices\Entities;

/**
 * Find channel variable properties entities query
 *
 * @template T of Entities\Channels\Properties\Variable
 * @extends  FindChannelProperties<T>
 */
class FindChannelVariableProperties extends FindChannelProperties
{

	public function byValue(string $value): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('p.value = :value')->setParameter('value', $value);
		};
	}

}
