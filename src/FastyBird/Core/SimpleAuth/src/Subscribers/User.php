<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           14.07.20
 */

namespace FastyBird\Core\SimpleAuth\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\SimpleAuth\Exceptions;
use FastyBird\Core\SimpleAuth\Mapping;
use FastyBird\Core\SimpleAuth\Security;
use Nette;
use ReflectionException;
use function array_key_exists;
use function is_array;

/**
 * Doctrine entities events
 *
 * @template T of object
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Subscribers
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class User implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/**
	 * @param Mapping\Driver\Owner<T> $driver
	 */
	public function __construct(
		private readonly Mapping\Driver\Owner $driver,
		private readonly Security\IUserStorage $userStorage,
	)
	{
	}

	/**
	 * Register events
	 *
	 * @return array<string>
	 */
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
					// @phpstan-ignore-next-line
					foreach ($config['create'] as $field) {
						// Field can not exist in change set, when persisting embedded document without parent for example
						// @phpstan-ignore-next-line
						$new = array_key_exists($field, $changeSet) ? $changeSet[$field][1] : false;

						if ($new === null) { // let manual values
							$needChanges = true;
							// @phpstan-ignore-next-line
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
		mixed $object,
		ORM\Mapping\ClassMetadata $classMetadata,
		string $field,
	): void
	{
		$property = $classMetadata->getReflectionProperty($field);

		// @phpstan-ignore-next-line
		$oldValue = $property->getValue($object);
		$newValue = $this->userStorage->getIdentity()?->getId()->toString();

		// @phpstan-ignore-next-line
		$property->setValue($object, $newValue);

		// @phpstan-ignore-next-line
		$uow->propertyChanged($object, $field, $oldValue, $newValue);
		// @phpstan-ignore-next-line
		$uow->scheduleExtraUpdate($object, [
			$field => [$oldValue, $newValue],
		]);
	}

	/**
	 * @throws Exceptions\InvalidMapping
	 * @throws ORM\Mapping\MappingException
	 * @throws Persistence\Mapping\MappingException
	 * @throws ReflectionException
	 */
	public function prePersist(
		mixed $entity,
		ORM\Event\PrePersistEventArgs $eventArgs,
	): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();
		/** @var ORM\Mapping\ClassMetadata<T> $classMetadata */
		$classMetadata = $manager->getClassMetadata($entity::class); // @phpstan-ignore-line

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
		mixed $object,
		ORM\Mapping\ClassMetadata $classMetadata,
	): void
	{
		foreach ($fields as $field) {
			// @phpstan-ignore-next-line
			if ($classMetadata->getReflectionProperty($field)->getValue($object) === null) { // let manual values
				$this->updateField($uow, $object, $classMetadata, $field);
			}
		}
	}

}
