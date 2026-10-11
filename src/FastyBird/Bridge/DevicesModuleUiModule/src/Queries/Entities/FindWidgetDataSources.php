<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Bridge\DevicesModuleUiModule\Entities as DevicesModuleUiModuleEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Queries as UiQueries;
use Ramsey\Uuid;

/**
 * Find widget data sources entities query
 *
 * @template T of DevicesModuleUiModuleEntities\Widgets\DataSources\Property
 * @extends  UiQueries\Entities\FindWidgetDataSources<T>
 */
class FindWidgetDataSources extends UiQueries\Entities\FindWidgetDataSources
{

	public function forProperty(DevicesEntities\Property $property): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.property', 'property');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('property.id = :property')->setParameter(
				'property',
				$property->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

}
