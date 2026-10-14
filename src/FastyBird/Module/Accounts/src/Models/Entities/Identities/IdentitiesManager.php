<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Models\Entities\Identities;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Accounts\Entities;
use Nette\Utils;
use ReflectionException;
use function assert;

/**
 * User identities entities manager
 */
final class IdentitiesManager
{

	/** @var Crud\EntityCrud<Entities\Identities\Identity>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Entities\Identities\Identity> $entityCrudFactory
	 */
	public function __construct(
		private readonly Crud\CrudFactory $entityCrudFactory,
	)
	{
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function create(
		array|Utils\ArrayHash $values,
	): Entities\Identities\Identity
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Identities\Identity);

		return $entity;
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 */
	public function update(
		Entities\Identities\Identity $entity,
		array|Utils\ArrayHash $values,
	): Entities\Identities\Identity
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Identities\Identity);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Entities\Identities\Identity $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return Crud\EntityCrud<Entities\Identities\Identity>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Identities\Identity::class);
		}

		return $this->entityCrud;
	}

}
