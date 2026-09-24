<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud;

use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping;

/**
 * Builds an EntityCrud instance bundling an entity's creator, updater and deleter for a given class
 *
 * @template T of Entities\CrudEntity
 */
final class EntityCrudFactory
{

	/**
	 * @param Create\EntityCreatorFactory<T> $entityCreatorFactory
	 * @param Update\EntityUpdaterFactory<T> $entityUpdaterFactory
	 * @param Delete\EntityDeleterFactory<T> $entityDeleterFactory
	 */
	public function __construct(
		private Mapping\IEntityMapper $entityMapper,
		private Create\EntityCreatorFactory $entityCreatorFactory,
		private Update\EntityUpdaterFactory $entityUpdaterFactory,
		private Delete\EntityDeleterFactory $entityDeleterFactory,
	)
	{
		// CRUD factories
	}

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityCrud<T>
	 */
	public function create(string $entityName): EntityCrud
	{
		return new EntityCrud(
			$entityName,
			$this->entityMapper,
			$this->entityCreatorFactory,
			$this->entityUpdaterFactory,
			$this->entityDeleterFactory,
		);
	}

}
