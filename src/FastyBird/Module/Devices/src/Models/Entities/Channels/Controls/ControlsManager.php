<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\Entities\Channels\Controls;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use Nette\Utils;
use Psr\EventDispatcher;
use ReflectionException;
use function assert;

/**
 * Channels controls entities manager
 */
final class ControlsManager
{

	/** @var Crud\EntityCrud<Entities\Channels\Controls\Control>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Entities\Channels\Controls\Control> $entityCrudFactory
	 */
	public function __construct(
		private readonly Crud\CrudFactory $entityCrudFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
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
	public function create(
		Utils\ArrayHash $values,
	): Entities\Channels\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Channels\Controls\Control);

		$this->dispatcher?->dispatch(new Events\EntityCreated($entity));

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
		Entities\Channels\Controls\Control $entity,
		Utils\ArrayHash $values,
	): Entities\Channels\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Channels\Controls\Control);

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Entities\Channels\Controls\Control $entity): bool
	{
		// Delete entity from database
		$result = $this->getEntityCrud()->getEntityDeleter()->delete($entity);

		if ($result) {
			$this->dispatcher?->dispatch(new Events\EntityDeleted($entity));
		}

		return $result;
	}

	/**
	 * @return Crud\EntityCrud<Entities\Channels\Controls\Control>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Channels\Controls\Control::class);
		}

		return $this->entityCrud;
	}

}
