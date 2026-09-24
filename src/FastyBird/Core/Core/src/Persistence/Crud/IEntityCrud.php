<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud;

use FastyBird\Core\Persistence\Entities;

/**
 * Interface exposing an entity's creator, updater and deleter as a single unit
 *
 * @template T of Entities\CrudEntity
 */
interface IEntityCrud
{

	/**
	 * @return  Create\EntityCreator<T>
	 */
	public function getEntityCreator(): Create\EntityCreator;

	/**
	 * @return  Update\EntityUpdater<T>
	 */
	public function getEntityUpdater(): Update\EntityUpdater;

	/**
	 * @return  Delete\EntityDeleter<T>
	 */
	public function getEntityDeleter(): Delete\EntityDeleter;

}
