<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Persistence\Exceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Mapping\Driver;
use Override;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use function array_key_exists;
use function is_array;

/**
 * Doctrine entities events
 *
 * @template T of object
 */
final readonly class User implements Common\EventSubscriber
{

	/**
	 * @param Driver\Owner<T> $driver
	 */
	public function __construct(
		private readonly Driver\Owner $driver,
		private readonly Identity\UserStorage $userStorage,
	)
	{
	}

	/**
	 * Register events
	 *
	 * @return array<string>
	 */
	#[Override]
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::loadClassMetadata,
			ORM\Events::onFlush,
		];
	}

	/**
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws InvalidArgumentException
	 */
	public function loadClassMetadata(
		ORM\Event\LoadClassMetadataEventArgs $eventArgs,
	): void
	{
		/** @var ORM\Mapping\ClassMetadata<T> $classMetadata */
		$classMetadata = $eventArgs->getClassMetadata();

		$this->driver->loadMetadataForObjectClass($eventArgs->getObjectManager(), $classMetadata);

		// Register pre persist event
		$this->registerEvent($classMetadata, ORM\Events::prePersist);
	}

	/**
	 * @param ORM\Mapping\ClassMetadata<T> $classMetadata
	 *
	 * @throws ORM\Mapping\MappingException
	 */
	private function registerEvent(
		ORM\Mapping\ClassMetadata $classMetadata,
		string $eventName,
	): void
	{
		if (!$this->hasRegisteredListener($classMetadata, $eventName, self::class)) {
			$classMetadata->addEntityListener($eventName, self::class, $eventName);
		}
	}

	/**
	 * @param ORM\Mapping\ClassMetadata<T> $classMetadata
	 */
	private function hasRegisteredListener(
		ORM\Mapping\ClassMetadata $classMetadata,
		string $eventName,
		string $listenerClass,
	): bool
	{
		if (!isset($classMetadata->entityListeners[$eventName])) {
			return false;
		}

		foreach ($classMetadata->entityListeners[$eventName] as $listener) {
			if ($listener['class'] === $listenerClass && $listener['method'] === $eventName) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws ORM\ORMInvalidArgumentException
	 * @throws Persistence\Mapping\MappingException
	 * @throws ReflectionException
	 * @throws InvalidArgumentException
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach ($uow->getScheduledEntityUpdates() as $object) {
			/** @var ORM\Mapping\ClassMetadata<T> $classMetadata */
			$classMetadata = $manager->getClassMetadata($object::class);

			$config = $this->driver->getObjectConfigurations($manager, $classMetadata->getName());

			if ($config !== []) {
				$changeSet = $uow->getEntityChangeSet($object);
				$needChanges = false;

				if ($uow->isScheduledForInsert($object) && isset($config['create'])) {
					// @phpstan-ignore foreach.nonIterable (Driver\Owner::getObjectConfigurations() returns an untyped array<mixed> cache blob)
					foreach ($config['create'] as $field) {
						// Field can not exist in change set, when persisting embedded document without parent for example
						// @phpstan-ignore argument.type (same untyped blob: $field comes out of $config['create'] as mixed)
						$new = array_key_exists($field, $changeSet) ? $changeSet[$field][1] : false;

						if ($new === null) { // let manual values
							$needChanges = true;
							$this->updateField($uow, $object, $classMetadata, $field);
						}
					}
				}

				if ($needChanges) {
					$uow->recomputeSingleEntityChangeSet($classMetadata, $object);
				}
			}
		}
	}

	/**
	 * Updates a field
	 *
	 * @param ORM\Mapping\ClassMetadata<T> $classMetadata
	 */
	private function updateField(
		ORM\UnitOfWork $uow,
		object $object,
		ORM\Mapping\ClassMetadata $classMetadata,
		string $field,
	): void
	{
		$property = $classMetadata->getReflectionProperty($field);

		// @phpstan-ignore method.nonObject (ClassMetadata::getReflectionProperty() is nullable, but the Owner driver maps every field it reports, so the property exists)
		$oldValue = $property->getValue($object);
		$newValue = $this->userStorage->identity?->getId()->toString();

		// @phpstan-ignore method.nonObject (same nullable getReflectionProperty() as above)
		$property->setValue($object, $newValue);

		$uow->propertyChanged($object, $field, $oldValue, $newValue);
		$uow->scheduleExtraUpdate($object, [
			$field => [$oldValue, $newValue],
		]);
	}

	/**
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws Persistence\Mapping\MappingException
	 * @throws ReflectionException
	 * @throws InvalidArgumentException
	 */
	public function prePersist(
		object $entity,
		ORM\Event\PrePersistEventArgs $eventArgs,
	): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();
		/** @var ORM\Mapping\ClassMetadata<T> $classMetadata */
		$classMetadata = $manager->getClassMetadata($entity::class);

		$config = $this->driver->getObjectConfigurations($manager, $classMetadata->getName());

		if ($config !== []) {
			if (isset($config['create']) && is_array($config['create'])) {
				$this->updateFields($config['create'], $uow, $entity, $classMetadata);
			}
		}
	}

	/**
	 * @param array<string> $fields
	 * @param ORM\Mapping\ClassMetadata<T> $classMetadata
	 */
	private function updateFields(
		array $fields,
		ORM\UnitOfWork $uow,
		object $object,
		ORM\Mapping\ClassMetadata $classMetadata,
	): void
	{
		foreach ($fields as $field) {
			// @phpstan-ignore method.deprecated (getPropertyAccessor() is not a drop-in replacement; updateField() carries the baselined use of the same call), method.nonObject (nullable getReflectionProperty(), as in updateField())
			if ($classMetadata->getReflectionProperty($field)->getValue($object) === null) { // let manual values
				$this->updateField($uow, $object, $classMetadata, $field);
			}
		}
	}

}
