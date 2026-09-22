<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Update;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Mapping\DoctrineCrud as Mapping;

/**
 * Interface for factories creating an EntityUpdater for a given entity class
 *
 * @template T of Entities\IEntity
 */
interface IEntityUpdater
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityUpdater<T>
	 */
	public function create(string $entityName, Mapping\IEntityMapper $entityMapper): EntityUpdater;

}
