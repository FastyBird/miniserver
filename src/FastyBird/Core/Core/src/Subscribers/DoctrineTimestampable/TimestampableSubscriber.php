<?php declare(strict_types = 1);

/**
 * TimestampableSubscriber.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           06.01.16
 */

namespace FastyBird\Core\Subscribers\DoctrineTimestampable;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Mapping\DoctrineTimestampable as Mapping;
use FastyBird\Core\Providers\DoctrineTimestampable as Providers;
use Nette;
use function array_key_exists;
use function date_default_timezone_get;
use function explode;
use function get_called_class;
use function in_array;
use function is_array;
use function is_object;
use function microtime;
use function number_format;
use function sprintf;
use function time;

/**
 * Doctrine timestampable subscriber
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class TimestampableSubscriber implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Mapping\Driver\Timestampable $driver,
		private readonly Providers\DateProvider|null $dateProvider = null,
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
	 * @throws ORM\Mapping\MappingException
	 */
	public function loadClassMetadata(ORM\Event\LoadClassMetadataEventArgs $eventArgs): void
	{
		/** @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata */
		$classMetadata = $eventArgs->getClassMetadata();

		$this->driver->loadMetadataForObjectClass($eventArgs->getObjectManager(), $classMetadata);

		// Register pre persist event
		$this->registerEvent($classMetadata, ORM\Events::prePersist);
		// Register pre update event
		$this->registerEvent($classMetadata, ORM\Events::preUpdate);
		// Register pre remove event
		$this->registerEvent($classMetadata, ORM\Events::preRemove);
	}

	/**
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function registerEvent(ORM\Mapping\ClassMetadata $classMetadata, string $eventName): void
	{
		// phpcs:ignore SlevomatCodingStandard.Classes.ModernClassNameReference.ClassNameReferencedViaFunctionCall
		if (!self::hasRegisteredListener($classMetadata, $eventName, get_called_class())) {
			// phpcs:ignore SlevomatCodingStandard.Classes.ModernClassNameReference.ClassNameReferencedViaFunctionCall
			$classMetadata->addEntityListener($eventName, get_called_class(), $eventName);
		}
	}

	/**
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private static function hasRegisteredListener(
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
	 * @throws ORM\Mapping\MappingException
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$em = $eventArgs->getObjectManager();
		$uow = $em->getUnitOfWork();

		// Check all scheduled updates
		foreach ($uow->getScheduledEntityUpdates() as $object) {
			/** @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata */
			$classMetadata = $em->getClassMetadata($object::class);

			$config = $this->driver->getObjectConfigurations($em, $classMetadata->getName());

			if ($config !== []) {
				$changeSet = $uow->getEntityChangeSet($object);
				$needChanges = false;

				if ($uow->isScheduledForInsert($object) && isset($config['create'])) {
					foreach ($config['create'] as $field) {
						// Field can not exist in change set, when persisting embedded document without parent for example
						$new = array_key_exists($field, $changeSet) ? $changeSet[$field][1] : false;

						if ($new === null) { // let manual values
							$needChanges = true;
							$this->updateField($uow, $object, $classMetadata, $field);
						}
					}
				}

				if (isset($config['update'])) {
					foreach ($config['update'] as $field) {
						$isInsertAndNull = $uow->isScheduledForInsert($object)
							&& array_key_exists($field, $changeSet)
							&& $changeSet[$field][1] === null;

						if (!isset($changeSet[$field]) || $isInsertAndNull) { // let manual values
							$needChanges = true;
							$this->updateField($uow, $object, $classMetadata, $field);
						}
					}
				}

				if (isset($config['delete'])) {
					foreach ($config['delete'] as $field) {
						$isDeleteAndNull = $uow->isScheduledForDelete($object)
							&& array_key_exists($field, $changeSet)
							&& $changeSet[$field][1] === null;

						if (!isset($changeSet[$field]) || $isDeleteAndNull) { // let manual values
							$needChanges = true;
							$this->updateField($uow, $object, $classMetadata, $field);
						}
					}
				}

				if (isset($config['change'])) {
					foreach ($config['change'] as $options) {
						if (isset($changeSet[$options['field']])) {
							continue; // Value was set manually
						}

						if (!is_array($options['trackedField'])) {
							$singleField = true;
							$trackedFields = [$options['trackedField']];

						} else {
							$singleField = false;
							$trackedFields = $options['trackedField'];
						}

						foreach ($trackedFields as $tracked) {
							$trackedChild = null;
							$parts = explode('.', $tracked);

							if (isset($parts[1])) {
								$tracked = $parts[0];
								$trackedChild = $parts[1];
							}

							if (isset($changeSet[$tracked])) {
								$changes = $changeSet[$tracked];

								if (isset($trackedChild)) {
									$changingObject = $changes[1];

									if (!is_object($changingObject)) {
										throw new Exceptions\UnexpectedValue(
											sprintf(
												'Field - [%s] is expected to be object in class - %s',
												$options['field'],
												$classMetadata->getName(),
											),
										);
									}

									/** @phpstan-var ORM\Mapping\ClassMetadata<object> $objectMeta */
									$objectMeta = $em->getClassMetadata($changingObject::class);
									$em->initializeObject($changingObject);
									$value = $objectMeta->getReflectionProperty($trackedChild)
										->getValue($changingObject);

								} else {
									$value = $changes[1];
								}

								if (
									(
										$singleField
										&& in_array(
											$value,
											(array) $options['value'],
											true,
										)
									)
									|| $options['value'] === null
								) {
									$needChanges = true;
									$this->updateField($uow, $object, $classMetadata, $options['field']);
								}
							}
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
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function updateField(
		ORM\UnitOfWork $uow,
		object $object,
		ORM\Mapping\ClassMetadata $classMetadata,
		string $field,
	): void
	{
		$property = $classMetadata->getReflectionProperty($field);

		$oldValue = $property->getValue($object);
		$newValue = $this->getDateValue($classMetadata, $field);

		$property->setValue($object, $newValue);

		$uow->propertyChanged($object, $field, $oldValue, $newValue);
		$uow->scheduleExtraUpdate($object, [
			$field => [$oldValue, $newValue],
		]);
	}

	/**
	 * Get the date value to set on a timestampable field
	 *
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function getDateValue(ORM\Mapping\ClassMetadata $classMetadata, string $field): DateTimeInterface|int
	{
		$mapping = $classMetadata->getFieldMapping($field);

		if (isset($mapping['type']) && $mapping['type'] === 'integer') {
			return $this->dateProvider?->getTimestamp() ?? time();
		}

		if ($this->dateProvider !== null) {
			return $this->dateProvider->getDate();
		}

		$dateTime = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));

		if ($dateTime !== false) {
			$dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));

			return $dateTime;
		}

		throw new Exceptions\InvalidState('Date could not be created');
	}

	/**
	 * @throws ORM\Mapping\MappingException
	 */
	public function prePersist(object $entity, ORM\Event\PrePersistEventArgs $eventArgs): void
	{
		$em = $eventArgs->getObjectManager();
		$uow = $em->getUnitOfWork();
		/** @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata */
		$classMetadata = $em->getClassMetadata($entity::class);

		$config = $this->driver->getObjectConfigurations($em, $classMetadata->getName());

		if ($config !== []) {
			foreach (['update', 'create'] as $event) {
				if (isset($config[$event])) {
					$this->updateFields($config[$event], $uow, $entity, $classMetadata);
				}
			}
		}
	}

	/**
	 * @throws ORM\Mapping\MappingException
	 */
	public function preUpdate(object $entity, ORM\Event\PreUpdateEventArgs $eventArgs): void
	{
		$em = $eventArgs->getObjectManager();
		$uow = $em->getUnitOfWork();
		/** @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata */
		$classMetadata = $em->getClassMetadata($entity::class);

		$config = $this->driver->getObjectConfigurations($em, $classMetadata->getName());

		if ($config !== [] && isset($config['update'])) {
			$this->updateFields($config['update'], $uow, $entity, $classMetadata);
		}
	}

	/**
	 * @throws ORM\Mapping\MappingException
	 */
	public function preRemove(object $entity, ORM\Event\PreRemoveEventArgs $eventArgs): void
	{
		$em = $eventArgs->getObjectManager();
		$uow = $em->getUnitOfWork();
		/** @phpstan-var ORM\Mapping\ClassMetadata<object> $classMetadata */
		$classMetadata = $em->getClassMetadata($entity::class);

		$config = $this->driver->getObjectConfigurations($em, $classMetadata->getName());

		if ($config !== [] && isset($config['delete'])) {
			$this->updateFields($config['delete'], $uow, $entity, $classMetadata);
		}
	}

	/**
	 * @param array<string> $fields
	 *
	 * @throws ORM\Mapping\MappingException
	 *
	 * @phpstan-param ORM\Mapping\ClassMetadata<object> $classMetadata
	 */
	private function updateFields(
		array $fields,
		ORM\UnitOfWork $uow,
		object $object,
		ORM\Mapping\ClassMetadata $classMetadata,
	): void
	{
		foreach ($fields as $field) {
			if ($classMetadata->getReflectionProperty($field)->getValue($object) === null) { // let manual values
				$this->updateField($uow, $object, $classMetadata, $field);
			}
		}
	}

}
