<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\Entities\Triggers;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Triggers\Entities;
use Nette;
use Nette\Utils;
use ReflectionException;
use function assert;

/**
 * Triggers entities manager
 */
final class TriggersManager
{

	use Nette\SmartObject;

	/** @var Crud\EntityCrud<Entities\Triggers\Trigger>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Entities\Triggers\Trigger> $entityCrudFactory
	 */
	public function __construct(
		private readonly Crud\CrudFactory $entityCrudFactory,
	)
	{
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function create(Utils\ArrayHash $values): Entities\Triggers\Trigger
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Triggers\Trigger);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 */
	public function update(
		Entities\Triggers\Trigger $entity,
		Utils\ArrayHash $values,
	): Entities\Triggers\Trigger
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Triggers\Trigger);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Entities\Triggers\Trigger $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return Crud\EntityCrud<Entities\Triggers\Trigger>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Triggers\Trigger::class);
		}

		return $this->entityCrud;
	}

}
