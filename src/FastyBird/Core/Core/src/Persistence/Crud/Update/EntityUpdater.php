<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Update;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Mapping;
use Nette\Utils;
use ReflectionException;

/**
 * Fills an existing entity's #[Crud]-marked properties from submitted values and persists it
 *
 * @template   T of Entities\CrudEntity
 * @extends    Crud\CrudManager<T>
 */
final class EntityUpdater extends Crud\CrudManager
{

	/**
	 * @param class-string<T> $entityName
	 */
	public function __construct(
		string $entityName,
		private readonly Mapping\EntityMapper $entityMapper,
		Persistence\ManagerRegistry $managerRegistry,
	)
	{
		parent::__construct($entityName, $managerRegistry);
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function update(Utils\ArrayHash $values, Entities\CrudEntity|int|string $entity): Entities\CrudEntity
	{
		if (!$entity instanceof Entities\CrudEntity) {
			$entity = $this->entityRepository->find($entity);
		}

		if (!$entity instanceof Entities\CrudEntity) {
			throw new CoreExceptions\InvalidArgument('Entity not found.');
		}

		$this->entityMapper->fillEntity($values, $entity, false);

		$this->entityManager->persist($entity);

		if ($this->getFlush() === true) {
			try {
				$this->entityManager->flush();
			} catch (ORM\Exception\ORMException $ex) {
				throw new CoreExceptions\InvalidState('Entity could not be updated', $ex->getCode(), $ex);
			}
		}

		return $entity;
	}

}
