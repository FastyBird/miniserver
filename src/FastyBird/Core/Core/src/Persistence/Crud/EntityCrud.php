<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud;

use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping;

/**
 * Bundles an entity's creator, updater and deleter behind a single facade
 *
 * @template    T of Entities\CrudEntity
 */
final class EntityCrud
{

	/**
	 * @param class-string<T> $entityName
	 * @param Create\EntityCreatorFactory<T> $entityCreatorFactory
	 * @param Update\EntityUpdaterFactory<T> $entityUpdaterFactory
	 * @param Delete\EntityDeleterFactory<T> $entityDeleterFactory
	 */
	public function __construct(
		private string $entityName,
		private Mapping\EntityMapper $entityMapper,
		private Create\EntityCreatorFactory $entityCreatorFactory,
		private Update\EntityUpdaterFactory $entityUpdaterFactory,
		private Delete\EntityDeleterFactory $entityDeleterFactory,
	)
	{
		// CRUD factories
	}

	public function getEntityCreator(): Create\EntityCreator
	{
		return $this->entityCreatorFactory->create($this->entityName, $this->entityMapper);
	}

	public function getEntityUpdater(): Update\EntityUpdater
	{
		return $this->entityUpdaterFactory->create($this->entityName, $this->entityMapper);
	}

	public function getEntityDeleter(): Delete\EntityDeleter
	{
		return $this->entityDeleterFactory->create($this->entityName);
	}

}
