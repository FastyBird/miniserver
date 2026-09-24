<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Update;

use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping;

/**
 * Interface for factories creating an EntityUpdater for a given entity class
 *
 * @template T of Entities\CrudEntity
 */
interface EntityUpdaterFactory
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityUpdater<T>
	 */
	public function create(string $entityName, Mapping\IEntityMapper $entityMapper): EntityUpdater;

}
