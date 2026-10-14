<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Commands;

use Contributte\Translation;
use Doctrine\Persistence;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models\Policies;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function array_values;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class CreateAccountTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testExecute(): void
	{
		$accountsManager = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class);

		$emailsRepository = $this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class);

		$emailsManager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$identitiesManager = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesManager::class);

		$enforcerFactory = $this->getContainer()->getByType(Identity\EnforcerFactory::class);

		$policiesRepository = $this->getContainer()->getByType(Policies\Repository::class);

		$identitiesRepository = $this->getContainer()->getByType(
			Models\Entities\Identities\IdentitiesRepository::class,
		);

		$translator = $this->getContainer()->getByType(Translation\Translator::class);

		$managerRegistry = $this->getContainer()->getByType(Persistence\ManagerRegistry::class);

		$application = new Application();
		$application->add(new Commands\Accounts\Create(
			$accountsManager,
			$emailsRepository,
			$emailsManager,
			$identitiesManager,
			$translator,
			$enforcerFactory,
			$policiesRepository,
			$managerRegistry,
		));

		$command = $application->get(Commands\Accounts\Create::NAME);

		$commandTester = new CommandTester($command);
		$result = $commandTester->execute([
			'lastName' => 'Balboa',
			'firstName' => 'Rocky',
			'email' => 'rocky@balboa.com',
			'password' => 'someRandomPassword',
			'role' => Identity\User::ROLE_USER,
		]);

		self::assertSame(0, $result);

		$findEmailQuery = new Queries\Entities\FindEmails();
		$findEmailQuery->byAddress('rocky@balboa.com');

		$email = $emailsRepository->findOneBy($findEmailQuery);

		self::assertNotNull($email);
		self::assertSame('Balboa Rocky', $email->getAccount()->getName());

		$findIdentity = new Queries\Entities\FindIdentities();
		$findIdentity->byUid('rocky@balboa.com');

		$identity = $identitiesRepository->findOneBy($findIdentity);

		self::assertNotNull($identity);

		$password = new Helpers\Password(
			null,
			'someRandomPassword',
			$identity->getSalt(),
		);

		self::assertSame($password->getHash(), $identity->getPassword()->getHash());
	}

	/**
	 * What the command stores, field by field: the account with its details, its default email
	 * address and its identity. It builds these as plain arrays since #723 and as ArrayHash, the
	 * details nested inside the account, before; this test passes on both.
	 *
	 * @throws Throwable
	 */
	public function testExecuteStoresTheAccountItsDetailsItsEmailAndItsIdentity(): void
	{
		$this->executeTheCommand();

		$entityManager = $this->getEntityManager();
		$entityManager->clear();

		$findEmailQuery = new Queries\Entities\FindEmails();
		$findEmailQuery->byAddress('rocky@balboa.com');

		$email = $this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class)
			->findOneBy($findEmailQuery);

		self::assertNotNull($email);
		self::assertSame('rocky@balboa.com', $email->getAddress());
		self::assertTrue($email->isDefault());
		self::assertFalse($email->isVerified());
		self::assertSame(Types\EmailVisibility::PUBLIC, $email->getVisibility());

		$account = $email->getAccount();

		self::assertSame(Types\AccountState::ACTIVE, $account->getState());
		self::assertNull($account->getRequestHash());
		self::assertSame('Rocky', $account->getDetails()->getFirstName());
		self::assertSame('Balboa', $account->getDetails()->getLastName());
		self::assertNull($account->getDetails()->getMiddleName());
		self::assertSame($email->getId()->toString(), $account->getEmail()?->getId()->toString());
		self::assertSame(
			[Identity\User::ROLE_USER],
			$this->getContainer()->getByType(Identity\EnforcerFactory::class)
				->getEnforcer()
				->getRolesForUser($account->getId()->toString()),
		);

		$identities = array_values($account->getIdentities());

		self::assertCount(1, $identities);
		self::assertSame('rocky@balboa.com', $identities[0]->getUid());
		self::assertSame(Types\IdentityState::ACTIVE, $identities[0]->getState());
		self::assertTrue($identities[0]->verifyPassword('someRandomPassword'));
	}

	/**
	 * Runs the command as testExecute() does
	 *
	 * @throws Throwable
	 */
	private function executeTheCommand(): void
	{
		$container = $this->getContainer();

		$application = new Application();
		$application->add(new Commands\Accounts\Create(
			$container->getByType(Models\Entities\Accounts\AccountsManager::class),
			$container->getByType(Models\Entities\Emails\EmailsRepository::class),
			$container->getByType(Models\Entities\Emails\EmailsManager::class),
			$container->getByType(Models\Entities\Identities\IdentitiesManager::class),
			$container->getByType(Translation\Translator::class),
			$container->getByType(Identity\EnforcerFactory::class),
			$container->getByType(Policies\Repository::class),
			$container->getByType(Persistence\ManagerRegistry::class),
		));

		$commandTester = new CommandTester($application->get(Commands\Accounts\Create::NAME));

		self::assertSame(0, $commandTester->execute([
			'lastName' => 'Balboa',
			'firstName' => 'Rocky',
			'email' => 'rocky@balboa.com',
			'password' => 'someRandomPassword',
			'role' => Identity\User::ROLE_USER,
		]));
	}

}
