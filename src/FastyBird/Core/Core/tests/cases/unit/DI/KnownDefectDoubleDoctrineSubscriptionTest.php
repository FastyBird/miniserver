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
 * KNOWN DEFECT, pinned on purpose: #564 (D2 of the Epic #459 census, #553 section 9).
 *
 * The Timestampable and Phone Doctrine subscribers are subscribed twice. nettrine's EventPass
 * adds every EventSubscriber service by name ("service@<name>"), and CoreExtension's
 * beforeCompile() adds the same two objects again through the entity manager's
 * addEventSubscriber(). Every entry is called, so TimestampableSubscriber runs twice on
 * loadClassMetadata and onFlush, and PhoneObjectSubscriber twice on loadClassMetadata.
 *
 * This test characterises the CURRENT behaviour so that the DI refactoring of Epic #459 cannot
 * change it unnoticed. It is not a statement that the behaviour is right. Fixing #564 changes
 * these counts, and this test is updated in the same change.
 */
final class KnownDefectDoubleDoctrineSubscriptionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testTimestampableAndPhoneSubscribersAreSubscribedTwice(): void
	{
		// Creating the entity manager runs its setups, the second subscription among them. It
		// connects to no database.
		$eventManager = $this->container->getByType(ORM\EntityManagerInterface::class)->getEventManager();

		$loadClassMetadata = $eventManager->getListeners('loadClassMetadata');

		self::assertSame(
			[
				PersistenceSubscribers\EntityDiscriminator::class,
				PersistenceSubscribers\TimestampableSubscriber::class,
				PhoneSubscribers\PhoneObjectSubscriber::class,
				PersistenceSubscribers\TimestampableSubscriber::class,
				PhoneSubscribers\PhoneObjectSubscriber::class,
			],
			self::classes($loadClassMetadata),
		);
		self::assertSame(3, self::distinctObjects($loadClassMetadata));
		self::assertSame(
			[true, true, true, false, false],
			self::subscribedByServiceName($loadClassMetadata),
		);

		$onFlush = $eventManager->getListeners('onFlush');

		self::assertSame(
			[
				PersistenceSubscribers\TimestampableSubscriber::class,
				PersistenceSubscribers\TimestampableSubscriber::class,
			],
			self::classes($onFlush),
		);
		self::assertSame(1, self::distinctObjects($onFlush));
		self::assertSame([true, false], self::subscribedByServiceName($onFlush));
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
