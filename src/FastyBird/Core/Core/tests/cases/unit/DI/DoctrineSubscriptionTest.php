<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Doctrine\ORM;
use FastyBird\Core\Persistence\Subscribers as PersistenceSubscribers;
use FastyBird\Core\Phone\Subscribers as PhoneSubscribers;
use FastyBird\Core\Tests;
use Nette;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function spl_object_id;
use function str_starts_with;

/**
 * Every Core Doctrine subscriber is on the event manager exactly once (#564)
 *
 * nettrine's EventPass subscribes every Doctrine\Common\EventSubscriber service by name
 * ("service@<name>"), and that is the only subscription. Until #564, the Persistence and Phone
 * extensions also called the entity manager's addEventSubscriber() for the Timestampable and
 * Phone subscribers, which added the same objects a second time, so TimestampableSubscriber ran
 * twice on loadClassMetadata and onFlush, and PhoneObjectSubscriber twice on loadClassMetadata.
 */
final class DoctrineSubscriptionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCoreSubscribersAreSubscribedOnce(): void
	{
		// Creating the entity manager runs every setup on it. It connects to no database.
		$eventManager = $this->container->getByType(ORM\EntityManagerInterface::class)->getEventManager();

		$loadClassMetadata = $eventManager->getListeners('loadClassMetadata');

		self::assertSame(
			[
				PersistenceSubscribers\EntityDiscriminator::class,
				PersistenceSubscribers\TimestampableSubscriber::class,
				PhoneSubscribers\PhoneObjectSubscriber::class,
			],
			self::classes($loadClassMetadata),
		);
		self::assertSame(3, self::distinctObjects($loadClassMetadata));
		self::assertSame([true, true, true], self::subscribedByServiceName($loadClassMetadata));

		$onFlush = $eventManager->getListeners('onFlush');

		self::assertSame(
			[
				PersistenceSubscribers\TimestampableSubscriber::class,
			],
			self::classes($onFlush),
		);
		self::assertSame(1, self::distinctObjects($onFlush));
		self::assertSame([true], self::subscribedByServiceName($onFlush));
	}

	/**
	 * @param array<object> $listeners
	 *
	 * @return list<string>
	 */
	private static function classes(array $listeners): array
	{
		return array_values(array_map(static fn (object $listener): string => $listener::class, $listeners));
	}

	/**
	 * @param array<object> $listeners
	 */
	private static function distinctObjects(array $listeners): int
	{
		$ids = array_map(static fn (object $listener): int => spl_object_id($listener), $listeners);

		return count(array_unique($ids));
	}

	/**
	 * nettrine keys an entry added by EventPass "service@<name>", and one added as an object by
	 * its object hash
	 *
	 * @param array<object> $listeners
	 *
	 * @return list<bool>
	 */
	private static function subscribedByServiceName(array $listeners): array
	{
		return array_map(
			static fn (int|string $key): bool => str_starts_with((string) $key, 'service@'),
			array_keys($listeners),
		);
	}

}
