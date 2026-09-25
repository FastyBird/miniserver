<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Entities\Policies;
use FastyBird\Core\Security\Identity;
use Override;
use function count;

/**
 * Casbin policy entity subscriber
 */
final readonly class Policy implements Common\EventSubscriber
{

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Identity\EnforcerFactory $enforcerFactory,
	)
	{
	}

	#[Override]
	public function getSubscribedEvents(): array
	{
		return [
			0 => ORM\Events::postPersist,
			1 => ORM\Events::postUpdate,
			2 => ORM\Events::postRemove,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Policies\Policy) {
			return;
		}

		$enforcer = $this->enforcerFactory->getEnforcer();

		$enforcer->invalidateCache();
		$enforcer->loadPolicy();
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
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
			!$entity instanceof Policies\Policy
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$enforcer = $this->enforcerFactory->getEnforcer();

		$enforcer->invalidateCache();
		$enforcer->loadPolicy();
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Policies\Policy) {
			return;
		}

		$enforcer = $this->enforcerFactory->getEnforcer();

		$enforcer->invalidateCache();
		$enforcer->loadPolicy();
	}

}
