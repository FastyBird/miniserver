<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\Entities\Triggers\Controls;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Triggers\Entities;
use Nette\Utils;
use ReflectionException;
use function assert;

/**
 * Triggers controls entities manager
 */
final class ControlsManager
{

	/** @var Crud\EntityCrud<Entities\Triggers\Controls\Control>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Entities\Triggers\Controls\Control> $entityCrudFactory
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
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Triggers\Controls\Control);

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
		Entities\Triggers\Controls\Control $entity,
		array|Utils\ArrayHash $values,
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Triggers\Controls\Control);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Entities\Triggers\Controls\Control $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return Crud\EntityCrud<Entities\Triggers\Controls\Control>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Triggers\Controls\Control::class);
		}

		return $this->entityCrud;
	}

}
