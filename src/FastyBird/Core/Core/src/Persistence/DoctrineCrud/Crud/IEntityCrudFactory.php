<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud;

use FastyBird\Core\Entities\DoctrineCrud as Entities;

/**
 * Interface for factories creating an EntityCrud for a given entity class
 *
 * @template T of Entities\IEntity
 */
interface IEntityCrudFactory
{

	/**
	 * @param class-string<T> $entityName

	 * @return  EntityCrud<T>
	 */
	public function create(string $entityName): EntityCrud;

}
