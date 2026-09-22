<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete;

use FastyBird\Core\Entities\DoctrineCrud as Entities;

/**
 * Interface for factories creating an EntityDeleter for a given entity class
 *
 * @template T of Entities\IEntity
 */
interface IEntityDeleter
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityDeleter<T>
	 */
	public function create(string $entityName): EntityDeleter;

}
