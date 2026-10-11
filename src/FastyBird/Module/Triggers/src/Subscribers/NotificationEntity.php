<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use Nette;
use Override;
use function array_merge;

/**
 * Trigger notification entity listener
 */
final class NotificationEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/**
	 * Register events
	 *
	 * @return array<string>
	 */
	#[Override]
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::onFlush,
		];
	}

	/**
	 * @throws Exceptions\UniqueNotificationEmailConstraint
	 * @throws Exceptions\UniqueNotificationNumberConstraint
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if ($object instanceof Entities\Notifications\Sms) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\Sms
						&& $notification->getPhone()->getInternationalNumber() === $object->getPhone()
							->getInternationalNumber()
					) {
						throw new Exceptions\UniqueNotificationNumberConstraint('Not same phone number in trigger');
					}
				}
			} elseif ($object instanceof Entities\Notifications\Email) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\Email
						&& $notification->getEmail() === $object->getEmail()
					) {
						throw new Exceptions\UniqueNotificationEmailConstraint('Not same email address in trigger');
					}
				}
			}
		}
	}

}
