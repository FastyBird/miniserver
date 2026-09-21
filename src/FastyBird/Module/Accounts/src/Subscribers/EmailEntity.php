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
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use Nette;
use function array_key_exists;
use function array_merge;
use function assert;
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

	public function __construct(private readonly Models\Entities\Emails\EmailsRepository $emailsRepository)
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
	 * @throws Exceptions\EmailAlreadyTaken
	 * @throws ApplicationExceptions\InvalidState
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
				throw new Exceptions\EmailAlreadyTaken('Given email is already taken');
			}
		}
	}

	/**
	 * @throws Exceptions\EmailHaveToBeDefault
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
				throw new Exceptions\EmailHaveToBeDefault('Default email address can not be made not default');
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
	 * @param ORM\Mapping\ClassMetadata<Entities\Emails\Email> $classMetadata
	 */
	private function setAsDefault(
		ORM\UnitOfWork $uow,
		ORM\Mapping\ClassMetadata $classMetadata,
		Entities\Emails\Email $email,
	): void
	{
		// ORM 3 deprecates getReflectionProperty() and types it nullable; getPropertyAccessor() is
		// the replacement and is what the ORM itself reads and writes mapped fields through.
		$property = $classMetadata->getPropertyAccessor('default');
		assert($property !== null);

		foreach ($email->getAccount()->getEmails() as $accountEmail) {
			// Deactivate all other user emails
			if (
				!$accountEmail->getId()
					->equals($email->getId())
				&& $accountEmail->isDefault()
			) {
				$accountEmail->setDefault(false);

				$oldValue = $property->getValue($email);

				$uow->propertyChanged($accountEmail, 'default', $oldValue, true);
				$uow->scheduleExtraUpdate($accountEmail, [
					'default' => [$oldValue, false],
				]);
			}
		}
	}

}
