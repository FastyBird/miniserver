<?php declare(strict_types = 1);

/**
 * Policy.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Core\Subscribers\SimpleAuth;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\SimpleAuth as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use Nette;
use function count;

/**
 * Casbin policy entity subscriber
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Policy implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Security\EnforcerFactory $enforcerFactory,
	)
	{
	}

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
		if (!$entity instanceof Entities\Policies\Policy) {
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
			!$entity instanceof Entities\Policies\Policy
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
		if (!$entity instanceof Entities\Policies\Policy) {
			return;
		}

		$enforcer = $this->enforcerFactory->getEnforcer();

		$enforcer->invalidateCache();
		$enforcer->loadPolicy();
	}

}
