<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use Override;
use function array_key_exists;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function spl_object_id;
use function sprintf;

/**
 * Doctrine entities events
 */
final readonly class AccountEntity implements Common\EventSubscriber
{

	private const array DEFAULT_ROLES = [
		Identity\User::ROLE_USER,
	];

	public const array SINGLE_ROLES = [
		Identity\User::ROLE_ADMINISTRATOR,
		Identity\User::ROLE_USER,
	];

	public const array NOT_ASSIGNABLE_ROLES = [
		Identity\User::ROLE_VISITOR,
		Identity\User::ROLE_ANONYMOUS,
	];

	public function __construct(
		private Identity\EnforcerFactory $enforcerFactory,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
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
	 * @throws AccountsExceptions\InvalidState
	 * @throws CoreExceptions\InvalidState
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
					Identity\User::ROLE_ADMINISTRATOR,
				) === []
				&& !$this->enforcerFactory->getEnforcer()->hasRoleForUser(
					$object->getId()->toString(),
					Identity\User::ROLE_ADMINISTRATOR,
				)
			) {
				throw new AccountsExceptions\InvalidState('First account have to be an administrator account');
			}
		}
	}

	/**
	 * @throws AccountsExceptions\AccountRoleInvalid
	 * @throws CoreExceptions\InvalidState
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		$this->removeTokensOfIdentitiesThatCannotSignIn($manager);

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
				foreach (self::DEFAULT_ROLES as $role) {
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
					in_array($role, self::SINGLE_ROLES, true)
					&& count($roles) > 1
				) {
					throw new AccountsExceptions\AccountRoleInvalid(
						sprintf('Role %s could not be combined with other roles', $role),
					);
				}

				/**
				 * Special roles like visitor or guest
				 * can not be assigned to account
				 */
				if (in_array($role, self::NOT_ASSIGNABLE_ROLES, true)) {
					throw new AccountsExceptions\AccountRoleInvalid(
						sprintf('Role %s could not be assigned to account', $role),
					);
				}
			}
		}
	}

	/**
	 * An identity that may no longer sign in loses its access tokens and their refresh tokens: its
	 * state, or its account's, is changed to anything but active, or it or its account is removed.
	 * They are removed as signing out removes them, in the same flush, so making the account or
	 * the identity active again does not make them valid again. Every writer that goes through the
	 * entity manager is covered: the HTTP API, the console and anything else.
	 */
	private function removeTokensOfIdentitiesThatCannotSignIn(ORM\EntityManagerInterface $manager): void
	{
		$uow = $manager->getUnitOfWork();

		$identities = [];

		foreach ($uow->getScheduledEntityUpdates() as $object) {
			// The change set holds an enum's backing value; the entity holds the enum
			if (!array_key_exists('state', $uow->getEntityChangeSet($object))) {
				continue;
			}

			if ($object instanceof Entities\Accounts\Account && !$object->isActivated()) {
				foreach ($object->getIdentities() as $identity) {
					$identities[spl_object_id($identity)] = $identity;
				}
			}

			if ($object instanceof Entities\Identities\Identity && !$object->isActive()) {
				$identities[spl_object_id($object)] = $object;
			}
		}

		foreach ($uow->getScheduledEntityDeletions() as $object) {
			if ($object instanceof Entities\Accounts\Account) {
				foreach ($object->getIdentities() as $identity) {
					$identities[spl_object_id($identity)] = $identity;
				}
			}

			if ($object instanceof Entities\Identities\Identity) {
				$identities[spl_object_id($object)] = $object;
			}
		}

		if ($identities === []) {
			return;
		}

		$accessTokens = $manager->getRepository(Entities\Tokens\AccessToken::class)->findBy([
			'identity' => array_values($identities),
		]);

		foreach ($accessTokens as $accessToken) {
			foreach ($accessToken->getChildren() as $refreshToken) {
				$manager->remove($refreshToken);
			}

			$manager->remove($accessToken);
		}
	}

}
