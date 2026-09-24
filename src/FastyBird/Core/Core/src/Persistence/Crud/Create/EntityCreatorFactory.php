<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Create;

use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping;

/**
 * Interface for factories creating an EntityCreator for a given entity class
 *
 * @template T of Entities\CrudEntity
 */
interface EntityCreatorFactory
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityCreator<T>
	 */
	public function create(string $entityName, Mapping\IEntityMapper $entityMapper): EntityCreator;

}
