<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud;

use FastyBird\Core\Persistence\Entities;

/**
 * Interface for factories creating an EntityCrud for a given entity class
 *
 * @template T of Entities\CrudEntity
 */
interface CrudFactory
{

	/**
	 * @param class-string<T> $entityName

	 * @return  EntityCrud<T>
	 */
	public function create(string $entityName): EntityCrud;

}
