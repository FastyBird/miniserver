<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Subscribers;

use Doctrine\DBAL;
use Doctrine\ORM;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Subscribers as PersistenceSubscribers;
use FastyBird\Core\Phone\Entities as PhoneEntities;
use FastyBird\Core\Phone\Exceptions as PhoneExceptions;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Subscribers as TriggersSubscribers;
use FastyBird\Module\Triggers\Tests;
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
 * created_at and updated_at on the SMS notifications the Triggers onFlush subscriber handles (#564)
 *
 * In production TimestampableSubscriber runs before the NotificationEntity subscriber on onFlush.
 * In this package's test container the module subscriber is defined first, so it would run first.
 * Every test here therefore puts the module onFlush subscribers back behind the Core one before
 * it writes anything, so that TimestampableSubscriber runs exactly where it runs in production.
 *
 * The phone column is checked as well, as stored and as loaded again through a cleared identity
 * map.
 *
 * The test container freezes the clock at 2020-04-01 12:00:00 UTC.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class NotificationTimestampsTest extends Tests\Cases\Unit\DbTestCase
{

	private const FROZEN_NOW = '2020-04-01 12:00:00';

	private const TRIGGER_ID = 'c64ba1c4-0eda-4cab-87a0-4d634f7b67f4';

	private const SMS_NOTIFICATION_ID = '4fe1019c-f49e-4cbf-83e6-20b394e76317';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws PersistenceExceptions\Query
	 * @throws PhoneExceptions\NoValidCountry
	 * @throws PhoneExceptions\NoValidPhone
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws TriggersExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testCreatedSmsNotificationIsStamped(): void
	{
		$this->runModuleOnFlushSubscribersAfterCore();

		$triggersRepository = $this->getContainer()->getByType(Models\Entities\Triggers\TriggersRepository::class);
		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$findTrigger = new Queries\Entities\FindTriggers();
		$findTrigger->byId(Uuid\Uuid::fromString(self::TRIGGER_ID));

		$trigger = $triggersRepository->findOneBy($findTrigger);

		self::assertNotNull($trigger);

		$notification = $manager->create(Utils\ArrayHash::from([
			'entity' => TriggersEntities\Notifications\Sms::class,
			'phone' => PhoneEntities\Phone::fromNumber('+420778776777'),
			'trigger' => $trigger,
		]));

		self::assertSame(
			[
				'created_at' => self::FROZEN_NOW,
				'updated_at' => self::FROZEN_NOW,
				'notification_phone' => '+420778776777',
			],
			$this->fetchRow($notification->getId()),
		);

		self::assertSame('+420778776777', $this->reloadSms($notification->getId())->getPhone()->getRawOutput());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\Query
	 * @throws PhoneExceptions\NoValidCountry
	 * @throws PhoneExceptions\NoValidPhone
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws TriggersExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testUpdatedSmsNotificationIsStamped(): void
	{
		$this->runModuleOnFlushSubscribersAfterCore();

		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$notification = $this->reloadSms(Uuid\Uuid::fromString(self::SMS_NOTIFICATION_ID));

		$manager->update($notification, Utils\ArrayHash::from([
			'phone' => PhoneEntities\Phone::fromNumber('+420778776778'),
		]));

		self::assertSame(
			[
				'created_at' => '2020-04-06 13:27:07',
				'updated_at' => self::FROZEN_NOW,
				'notification_phone' => '+420778776778',
			],
			$this->fetchRow($notification->getId()),
		);

		self::assertSame('+420778776778', $this->reloadSms($notification->getId())->getPhone()->getRawOutput());
	}

	/**
	 * Moves the module onFlush subscribers behind the Core one, which is their production order,
	 * and checks that the result really is that order
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function runModuleOnFlushSubscribersAfterCore(): void
	{
		$eventManager = $this->getEntityManager()->getEventManager();
		// nettrine's manager, which also takes a service name: that is how EventPass subscribed them
		assert($eventManager instanceof NettrineEvents\ContainerEventManager);

		$moduleSubscribers = [TriggersSubscribers\NotificationEntity::class];
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

		// The Core subscriber first, then exactly the one Triggers subscriber
		self::assertSame(
			[PersistenceSubscribers\TimestampableSubscriber::class],
			array_values(array_unique(array_slice($classes, 0, -1))),
		);
		self::assertSame($moduleSubscribers, array_slice($classes, -1));
	}

	/**
	 * Reads the stored values straight from the table, not from the identity map
	 *
	 * @return array<string, mixed>|false
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function fetchRow(Uuid\UuidInterface $id): array|false
	{
		return $this->getDb()->fetchAssociative(
			'SELECT created_at, updated_at, notification_phone FROM fb_triggers_module_notifications'
			. ' WHERE notification_id = ?',
			[$id->getBytes()],
		);
	}

	/**
	 * Loads the notification again from the database, through a cleared identity map
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws TriggersExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function reloadSms(Uuid\UuidInterface $id): TriggersEntities\Notifications\Sms
	{
		$this->getEntityManager()->clear();

		$findNotification = new Queries\Entities\FindNotifications();
		$findNotification->byId($id);

		$notification = $this->getContainer()
			->getByType(Models\Entities\Notifications\NotificationsRepository::class)
			->findOneBy($findNotification);

		self::assertInstanceOf(TriggersEntities\Notifications\Sms::class, $notification);

		return $notification;
	}

}
