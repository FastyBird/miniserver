<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete;

use FastyBird\Core\Entities\DoctrineCrud as Entities;

/**
 * Doctrine CRUD entity deleter factory
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
