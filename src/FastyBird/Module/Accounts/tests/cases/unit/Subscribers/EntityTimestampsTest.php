<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Subscribers;

use Doctrine\DBAL;
use Doctrine\ORM;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Subscribers as PersistenceSubscribers;
use FastyBird\Core\Security\Subscribers as SecuritySubscribers;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Subscribers as AccountsSubscribers;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types;
use Nette;
use Nette\Utils;
use Nettrine\ORM\Events as NettrineEvents;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use function array_map;
use function array_slice;
use function array_unique;
use function array_values;
use function assert;
use function in_array;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * created_at and updated_at on the entities the Accounts onFlush subscribers handle (#564)
 *
 * In production the Core onFlush subscribers (Security's User, then TimestampableSubscriber) run
 * before the AccountEntity and EmailEntity subscribers. In this package's test container the
 * module subscribers are defined first, so they would run first. Every test here therefore puts
 * the module onFlush subscribers back behind the Core ones before it writes anything, so that
 * TimestampableSubscriber runs exactly where it runs in production.
 *
 * The test container freezes the clock at 2020-04-01 12:00:00 UTC, which is after every
 * timestamp the fixtures load for these rows.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class EntityTimestampsTest extends Tests\Cases\Unit\DbTestCase
{

	private const FROZEN_NOW = '2020-04-01 12:00:00';

	private const ACCOUNT_ID = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testUpdatedAccountIsStamped(): void
	{
		$this->runModuleOnFlushSubscribersAfterCore();

		$repository = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsRepository::class);
		$manager = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class);

		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->byId(Uuid\Uuid::fromString(self::ACCOUNT_ID));

		$account = $repository->findOneBy($findAccount);

		self::assertNotNull($account);

		$manager->update($account, Utils\ArrayHash::from([
			'state' => Types\AccountState::BLOCKED,
		]));

		self::assertSame(
			['created_at' => '2017-01-03 11:30:00', 'updated_at' => self::FROZEN_NOW],
			$this->fetchTimestamps('fb_accounts_module_accounts', 'account_id', self::ACCOUNT_ID),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\Query
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testEmailMadeDefaultIsStamped(): void
	{
		$this->runModuleOnFlushSubscribersAfterCore();

		$repository = $this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class);
		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$email = $repository->findOneByAddress('john.doe@fastybird.ovh');

		self::assertNotNull($email);

		$manager->update($email, Utils\ArrayHash::from([
			'default' => true,
		]));

		self::assertSame(
			['created_at' => '2017-09-07 18:24:35', 'updated_at' => self::FROZEN_NOW],
			$this->fetchTimestamps('fb_accounts_module_emails', 'email_id', $email->getId()->toString()),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testCreatedEmailIsStamped(): void
	{
		$this->runModuleOnFlushSubscribersAfterCore();

		$repository = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsRepository::class);
		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->byId(Uuid\Uuid::fromString(self::ACCOUNT_ID));

		$account = $repository->findOneBy($findAccount);

		self::assertNotNull($account);

		$email = $manager->create(Utils\ArrayHash::from([
			'account' => $account,
			'address' => 'john.doe@timestamps.test',
		]));

		self::assertSame(
			['created_at' => self::FROZEN_NOW, 'updated_at' => self::FROZEN_NOW],
			$this->fetchTimestamps('fb_accounts_module_emails', 'email_id', $email->getId()->toString()),
		);
	}

	/**
	 * Moves the module onFlush subscribers behind the Core ones, which is their production order,
	 * and checks that the result really is that order
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function runModuleOnFlushSubscribersAfterCore(): void
	{
		$eventManager = $this->getEntityManager()->getEventManager();
		// nettrine's manager, which also takes a service name: that is how EventPass subscribed them
		assert($eventManager instanceof NettrineEvents\ContainerEventManager);

		$moduleSubscribers = [AccountsSubscribers\AccountEntity::class, AccountsSubscribers\EmailEntity::class];
		$moduleListeners = [];

		foreach ($eventManager->getListeners(ORM\Events::onFlush) as $key => $listener) {
			if (!in_array($listener::class, $moduleSubscribers, true)) {
				continue;
			}

			$eventManager->removeEventListener(
				ORM\Events::onFlush,
				str_starts_with((string) $key, 'service@') ? substr((string) $key, strlen('service@')) : $listener,
			);

			$moduleListeners[] = $listener;
		}

		foreach ($moduleListeners as $listener) {
			$eventManager->addEventListener(ORM\Events::onFlush, $listener);
		}

		$classes = array_values(array_map(
			static fn (object $listener): string => $listener::class,
			$eventManager->getListeners(ORM\Events::onFlush),
		));

		// The Core subscribers first, in their own order, then exactly the two Accounts ones
		self::assertSame(
			[SecuritySubscribers\User::class, PersistenceSubscribers\TimestampableSubscriber::class],
			array_values(array_unique(array_slice($classes, 0, -2))),
		);
		self::assertSame($moduleSubscribers, array_slice($classes, -2));
	}

	/**
	 * Reads the stored values straight from the table, not from the identity map
	 *
	 * @return array<string, mixed>|false
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function fetchTimestamps(string $table, string $idColumn, string $id): array|false
	{
		return $this->getDb()->fetchAssociative(
			'SELECT created_at, updated_at FROM ' . $table . ' WHERE ' . $idColumn . ' = ?',
			[Uuid\Uuid::fromString($id)->getBytes()],
		);
	}

}
