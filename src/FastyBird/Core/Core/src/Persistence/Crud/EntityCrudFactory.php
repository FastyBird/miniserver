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

	/** @var Create\EntityCreatorFactory<T> */
	private Create\EntityCreatorFactory $entityCreatorFactory;

	/** @var Update\EntityUpdaterFactory<T> */
	private Update\EntityUpdaterFactory $entityUpdaterFactory;

	/** @var Delete\EntityDeleterFactory<T> */
	private Delete\EntityDeleterFactory $entityDeleterFactory;

	/**
	 * @param Create\EntityCreatorFactory<T> $entityCreatorFactory
	 * @param Update\EntityUpdaterFactory<T> $entityUpdaterFactory
	 * @param Delete\EntityDeleterFactory<T> $entityDeleterFactory
	 */
	public function __construct(
		private Mapping\IEntityMapper $entityMapper,
		Create\EntityCreatorFactory $entityCreatorFactory,
		Update\EntityUpdaterFactory $entityUpdaterFactory,
		Delete\EntityDeleterFactory $entityDeleterFactory,
	)
	{
		// CRUD factories
		$this->entityCreatorFactory = $entityCreatorFactory;
		$this->entityUpdaterFactory = $entityUpdaterFactory;
		$this->entityDeleterFactory = $entityDeleterFactory;
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
