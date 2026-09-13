<?php declare(strict_types = 1);

/**
 * AccountEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           18.08.20
 */

namespace FastyBird\Module\Accounts\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\SimpleAuth;
use FastyBird\Core\SimpleAuth\Exceptions as SimpleAuthExceptions;
use FastyBird\Core\SimpleAuth\Security as SimpleAuthSecurity;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use Nette;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly SimpleAuthSecurity\EnforcerFactory $enforcerFactory,
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
	 * @throws Exceptions\InvalidState
	 * @throws SimpleAuthExceptions\InvalidState
	 */
	public function prePersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach ($uow->getScheduledEntityInsertions() as $object) {
			if (
				$object instanceof Entities\Accounts\Account
				&& $this->enforcerFactory->getEnforcer()->getUsersForRole(
					SimpleAuth\Constants::ROLE_ADMINISTRATOR,
				) === []
				&& !$this->enforcerFactory->getEnforcer()->hasRoleForUser(
					$object->getId()->toString(),
					SimpleAuth\Constants::ROLE_ADMINISTRATOR,
				)
			) {
				throw new Exceptions\InvalidState('First account have to be an administrator account');
			}
		}
	}

	/**
	 * @throws Exceptions\AccountRoleInvalid
	 * @throws SimpleAuthExceptions\InvalidState
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if (!$object instanceof Entities\Accounts\Account) {
				continue;
			}

			/**
			 * If new account is without any role
			 * we have to assign default roles
			 */
			$roles = $this->enforcerFactory->getEnforcer()->getRolesForUser($object->getId()->toString());

			if ($roles === []) {
				foreach (Accounts\Constants::DEFAULT_ROLES as $role) {
					$this->enforcerFactory->getEnforcer()->addRoleForUser($object->getId()->toString(), $role);
					$this->enforcerFactory->getEnforcer()->invalidateCache();
				}
			}

			foreach ($roles as $role) {
				/**
				 * Special roles like administrator or user
				 * can not be assigned to account with other roles
				 */
				if (
					in_array($role, Accounts\Constants::SINGLE_ROLES, true)
					&& count($roles) > 1
				) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be combined with other roles', $role),
					);
				}

				/**
				 * Special roles like visitor or guest
				 * can not be assigned to account
				 */
				if (in_array($role, Accounts\Constants::NOT_ASSIGNABLE_ROLES, true)) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be assigned to account', $role),
					);
				}
			}
		}
	}

}
