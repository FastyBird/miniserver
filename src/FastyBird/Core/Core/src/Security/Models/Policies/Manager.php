<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\Policies;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Entities\Policies;
use Nette\Utils;
use function assert;

/**
 * Security tokens entities manager
 */
final class Manager
{

	/** @var Crud\IEntityCrud<Policies\Policy>|null */
	private Crud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Policies\Policy> $entityCrudFactory
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
	 */
	public function create(Utils\ArrayHash $values): Policies\Policy
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Policies\Policy);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function update(
		Policies\Policy $entity,
		Utils\ArrayHash $values,
	): Policies\Policy
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Policies\Policy);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Policies\Policy $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return Crud\IEntityCrud<Policies\Policy>
	 */
	public function getEntityCrud(): Crud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Policies\Policy::class);
		}

		return $this->entityCrud;
	}

}
