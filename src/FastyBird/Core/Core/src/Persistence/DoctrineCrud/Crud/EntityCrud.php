<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Mapping\DoctrineCrud as Mapping;
use FastyBird\Core\Persistence\DoctrineCrud\Crud;
use Nette;

/**
 * Bundles an entity's creator, updater and deleter behind a single facade
 *
 * @template    T of Entities\IEntity
 * @implements  IEntityCrud<T>
 */
final class EntityCrud implements IEntityCrud
{

	use Nette\SmartObject;

	/**
	 * @param class-string<T> $entityName
	 * @param Crud\Create\IEntityCreator<T> $entityCreatorFactory
	 * @param Crud\Update\IEntityUpdater<T> $entityUpdaterFactory
	 * @param Crud\Delete\IEntityDeleter<T> $entityDeleterFactory
	 */
	public function __construct(
		private string $entityName,
		private Mapping\IEntityMapper $entityMapper,
		private Crud\Create\IEntityCreator $entityCreatorFactory,
		private Crud\Update\IEntityUpdater $entityUpdaterFactory,
		private Crud\Delete\IEntityDeleter $entityDeleterFactory,
	)
	{
		// CRUD factories
	}

	public function getEntityCreator(): Crud\Create\EntityCreator
	{
		return $this->entityCreatorFactory->create($this->entityName, $this->entityMapper);
	}

	public function getEntityUpdater(): Crud\Update\EntityUpdater
	{
		return $this->entityUpdaterFactory->create($this->entityName, $this->entityMapper);
	}

	public function getEntityDeleter(): Crud\Delete\EntityDeleter
	{
		return $this->entityDeleterFactory->create($this->entityName);
	}

}
