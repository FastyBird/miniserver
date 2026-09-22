<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Update;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Mapping\DoctrineCrud as Mapping;
use FastyBird\Core\Persistence\DoctrineCrud\Crud;
use Nette\Utils;

/**
 * Fills an existing entity's #[Crud]-marked properties from submitted values and persists it
 *
 * @template   T of Entities\IEntity
 * @extends    Crud\CrudManager<T>
 */
final class EntityUpdater extends Crud\CrudManager
{

	/** @var array<callable(Entities\IEntity, Utils\ArrayHash): void> */
	public array $beforeAction = [];

	/** @var array<callable(Entities\IEntity, Utils\ArrayHash): void> */
	public array $afterAction = [];

	/**
	 * @param class-string<T> $entityName
	 */
	public function __construct(
		string $entityName,
		private readonly Mapping\IEntityMapper $entityMapper,
		Persistence\ManagerRegistry $managerRegistry,
	)
	{
		parent::__construct($entityName, $managerRegistry);
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function update(Utils\ArrayHash $values, Entities\IEntity|int|string $entity): Entities\IEntity
	{
		if (!$entity instanceof Entities\IEntity) {
			$entity = $this->entityRepository->find($entity);
		}

		if (!$entity instanceof Entities\IEntity) {
			throw new Exceptions\InvalidArgument('Entity not found.');
		}

		Utils\Arrays::invoke($this->beforeAction, $entity, $values);

		$this->entityMapper->fillEntity($values, $entity, false);

		$this->entityManager->persist($entity);

		Utils\Arrays::invoke($this->afterAction, $entity, $values);

		if ($this->getFlush() === true) {
			try {
				$this->entityManager->flush();
			} catch (ORM\Exception\ORMException $ex) {
				throw new Exceptions\InvalidState('Entity could not be updated', $ex->getCode(), $ex);
			}
		}

		return $entity;
	}

}
