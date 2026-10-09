<?php declare(strict_types = 1);

/**
 * EmailEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Utilities;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Models;
use Nette;
use function array_key_exists;
use function array_merge;
use function count;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Entities\Emails\EmailsRepository $emailsRepository,
		private readonly Utilities\DateTimeProvider $dateProvider,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::prePersist,
			ORM\Events::onFlush,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws AccountsExceptions\EmailAlreadyTaken
	 * @throws CoreExceptions\InvalidState
	 */
	public function prePersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach ($uow->getScheduledEntityInsertions() as $object) {
			if (!$object instanceof Entities\Emails\Email) {
				continue;
			}

			$foundEmail = $this->emailsRepository->findOneByAddress($object->getAddress());

			if ($foundEmail !== null && !$foundEmail->getId()->equals($object->getId())) {
				throw new AccountsExceptions\EmailAlreadyTaken('Given email is already taken');
			}
		}
	}

	/**
	 * @throws AccountsExceptions\EmailHaveToBeDefault
	 * @throws ORM\ORMInvalidArgumentException
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			$changeSet = $uow->getEntityChangeSet($object);

			if (
				array_key_exists('default', $changeSet)
				&& count($changeSet['default']) === 2
				&& $changeSet['default'][0] === true
				&& $changeSet['default'][1] === false
			) {
				throw new AccountsExceptions\EmailHaveToBeDefault('Default email address can not be made not default');
			}

			if ($object instanceof Entities\Emails\Email && $object->isDefault()) {
				/** @var ORM\Mapping\ClassMetadata<Entities\Emails\Email> $classMetadata */
				$classMetadata = $manager->getClassMetadata($object::class);

				// Check if entity was set as default
				if (array_key_exists('default', $changeSet)) {
					$this->setAsDefault($uow, $classMetadata, $object);
				}
			}
		}
	}

	/**
	 * Switches the account's other default emails off
	 *
	 * This runs in onFlush, after the change sets were computed and after TimestampableSubscriber
	 * has stamped what it found in them. The demoted email is therefore stamped here, from the same
	 * date provider, and its change set is recomputed, which makes it a regular scheduled update:
	 * written with the other updates, seen by the update listeners, and with its original data
	 * synchronised, so a later flush does not find the demotion again (#593).
	 *
	 * @param ORM\Mapping\ClassMetadata<Entities\Emails\Email> $classMetadata
	 *
	 * @throws ORM\ORMInvalidArgumentException
	 */
	private function setAsDefault(
		ORM\UnitOfWork $uow,
		ORM\Mapping\ClassMetadata $classMetadata,
		Entities\Emails\Email $email,
	): void
	{
		foreach ($email->getAccount()->getEmails() as $accountEmail) {
			// Deactivate all other user emails
			if (
				!$accountEmail->getId()
					->equals($email->getId())
				&& $accountEmail->isDefault()
			) {
				$accountEmail->setDefault(false);
				$accountEmail->setUpdatedAt($this->dateProvider->getDate());

				$uow->recomputeSingleEntityChangeSet($classMetadata, $accountEmail);
			}
		}
	}

}
