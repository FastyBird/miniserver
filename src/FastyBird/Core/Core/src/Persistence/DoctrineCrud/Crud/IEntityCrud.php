<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Persistence\DoctrineCrud\Crud;

/**
 * Interface exposing an entity's creator, updater and deleter as a single unit
 *
 * @template T of Entities\IEntity
 */
interface IEntityCrud
{

	/**
	 * @return  Crud\Create\EntityCreator<T>
	 */
	public function getEntityCreator(): Crud\Create\EntityCreator;

	/**
	 * @return  Crud\Update\EntityUpdater<T>
	 */
	public function getEntityUpdater(): Crud\Update\EntityUpdater;

	/**
	 * @return  Crud\Delete\EntityDeleter<T>
	 */
	public function getEntityDeleter(): Crud\Delete\EntityDeleter;

}
