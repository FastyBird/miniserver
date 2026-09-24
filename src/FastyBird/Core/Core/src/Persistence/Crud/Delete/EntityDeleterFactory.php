<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Delete;

use FastyBird\Core\Persistence\Entities;

/**
 * Interface for factories creating an EntityDeleter for a given entity class
 *
 * @template T of Entities\CrudEntity
 */
interface EntityDeleterFactory
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityDeleter<T>
	 */
	public function create(string $entityName): EntityDeleter;

}
