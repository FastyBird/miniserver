<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Create;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Mapping;
use Nette\Utils;
use ReflectionClass;
use ReflectionException;
use function class_exists;
use function is_string;
use function sprintf;

/**
 * Instantiates a new entity, fills its #[Crud]-marked properties from submitted values and persists it
 *
 * @template   T of Entities\CrudEntity
 * @extends    Crud\CrudManager<T>
 */
final class EntityCreator extends Crud\CrudManager
{

	/** @var array<callable(Entities\CrudEntity, Utils\ArrayHash): void> */
	public array $beforeAction = [];

	/** @var array<callable(Entities\CrudEntity, Utils\ArrayHash): void> */
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
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function create(Utils\ArrayHash $values, Entities\CrudEntity|null $entity = null): Entities\CrudEntity
	{
		if (!$entity instanceof Entities\CrudEntity) {
			try {
				// Entity name is override
				$entityClass = $values->offsetExists('entity')
					&& is_string($values->offsetGet('entity'))
					&& class_exists($values->offsetGet('entity'))
						? $values->offsetGet('entity')
						: $this->entityName;

				try {
					if (class_exists($entityClass)) {
						$rc = new ReflectionClass($entityClass);

					} else {
						throw new CoreExceptions\InvalidState('Entity could not be parsed');
					}
				} catch (ReflectionException) {
					throw new CoreExceptions\InvalidState('Entity could not be parsed');
				}

				if ($rc->isAbstract()) {
					throw new CoreExceptions\InvalidArgument(
						sprintf('Abstract entity "%s" can not be used.', $entityClass),
					);
				}

				$constructor = $rc->getConstructor();

				$entity = $constructor !== null ? $rc->newInstanceArgs(
					Helpers\ConstructorAutowiring::autowireArguments($constructor, (array) $values),
				) : $this->entityManager->getClassMetadata($this->entityName)
					->newInstance();
			} catch (ReflectionException) {
				// Class could not be parsed
			}
		}

		if ($entity === null || !$entity instanceof Entities\CrudEntity) {
			throw new CoreExceptions\InvalidArgument('Entity could not be created.');
		}

		Utils\Arrays::invoke($this->beforeAction, $entity, $values);

		$this->entityMapper->fillEntity($values, $entity, true);

		$this->entityManager->persist($entity);

		Utils\Arrays::invoke($this->afterAction, $entity, $values);

		if ($this->getFlush()) {
			try {
				$this->entityManager->flush();
			} catch (ORM\Exception\ORMException $ex) {
				throw new CoreExceptions\InvalidState('Entity could not be created', $ex->getCode(), $ex);
			}
		}

		return $entity;
	}

}
