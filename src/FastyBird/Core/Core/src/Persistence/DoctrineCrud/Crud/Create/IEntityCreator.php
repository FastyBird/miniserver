<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Create;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Mapping\DoctrineCrud as Mapping;

/**
 * Doctrine CRUD entity creator factory
 *
 * @template T of Entities\IEntity
 */
interface IEntityCreator
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityCreator<T>
	 */
	public function create(string $entityName, Mapping\IEntityMapper $entityMapper): EntityCreator;

}
