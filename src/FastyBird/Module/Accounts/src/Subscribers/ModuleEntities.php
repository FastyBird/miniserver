<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Module\Accounts\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use Nette;
use Nette\Utils;
use ReflectionClass;
use function count;
use function is_a;
use function str_starts_with;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntities implements Common\EventSubscriber
{

	use Nette\SmartObject;

	private const ACTION_CREATED = 'created';

	private const ACTION_UPDATED = 'updated';

	private const ACTION_DELETED = 'deleted';

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly EventLoop\Status $eventLoopStatus,
		private readonly Documents\RoutingDocumentFactory $documentFactory,
		private readonly Publisher\MessagePublisher $publisher,
		private readonly Async\MessagePublisher $asyncPublisher,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::postRemove,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeSet = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeSet) === 0) {
			return;
		}

		// Check for valid entity
		if (
			!$entity instanceof Entities\Entity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_DELETED);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	private function publishEntity(Entities\Entity $entity, string $action): void
	{
		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (Accounts\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (Accounts\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (Accounts\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			$this->getPublisher($this->eventLoopStatus->isRunning())->publish(
				Sources\Module::ACCOUNTS,
				$publishRoutingKey,
				$this->documentFactory->create(
					Utils\ArrayHash::from($entity->toArray()),
					$publishRoutingKey,
				),
			);
		}
	}

	private function validateNamespace(object $entity): bool
	{
		$rc = new ReflectionClass($entity);

		if (str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Accounts')) {
			return true;
		}

		foreach ($rc->getInterfaces() as $interface) {
			if (str_starts_with($interface->getNamespaceName(), 'FastyBird\Module\Accounts')) {
				return true;
			}
		}

		return false;
	}

	private function getPublisher(bool $async): Publisher\MessagePublisher|Async\MessagePublisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
