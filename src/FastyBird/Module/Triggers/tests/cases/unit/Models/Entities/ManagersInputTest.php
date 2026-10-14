<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Models\Entities;

use BackedEnum;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Tests;
use FastyBird\Module\Triggers\Types;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_shift;
use function assert;
use function is_array;
use function is_object;
use function ksort;
use function sort;
use function sprintf;
use function str_replace;

/**
 * Triggers' five entity managers take a plain array as well as an ArrayHash (#723).
 *
 * Each test makes one change twice: once from ArrayHash values, which is rolled back, and once
 * from the equal plain array. It compares the two results: every mapped field and every
 * association of every entity reachable from the ones the change returns, as returned and again
 * as read back from the database. Against the code before #723 the plain array is a TypeError.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ManagersInputTest extends Tests\Cases\Unit\DbTestCase
{

	// Movie Night, a manual trigger, and Rise n'Shine, an automatic one, in the fixtures
	private const string MANUAL_TRIGGER = '421ca8e9-26c6-4630-89ba-c53aea9bcb1e';

	private const string AUTOMATIC_TRIGGER = '1b17bcaa-a19e-45f0-98b4-56211cc648ae';

	private const string ACTION = '46c39a95-39eb-4216-9fa3-4d575a6295bd';

	private const string CONDITION = '09c453b3-c55f-4050-8f1c-b50f8d5728c2';

	private const string NOTIFICATION = '05f28df9-5f19-4923-b3f8-b9090116dadc';

	private const string NEW_TRIGGER = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c01';

	private const string NEW_ACTION = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c02';

	private const string NEW_NESTED_ACTION = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c03';

	private const string NEW_CONDITION = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c04';

	private const string NEW_NOTIFICATION = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c05';

	private const string NEW_NESTED_NOTIFICATION = 'd2a6c0b4-3f1e-4c7d-8a5b-9e0f1a2b3c06';

	private const string DO_ITEM = 'a830828c-6768-4274-b909-20ce0e222347';

	private const string WATCH_ITEM = '51d05010-c079-494c-ba73-9ea206f6bad1';

	/**
	 * A manual trigger with nested params, a nested action of a type chosen by `entity`, and a
	 * nested email notification (one-to-many items created with it)
	 *
	 * @throws Throwable
	 */
	public function testATriggerCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Triggers\TriggersManager::class);

		$values = static fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_TRIGGER),
			'entity' => Entities\Triggers\Manual::class,
			'name' => 'Reading Time',
			'comment' => 'A nested graph',
			'enabled' => false,
			'params' => ['scene' => ['brightness' => 40, 'colors' => ['warm', 'white']]],
			'actions' => [
				[
					'id' => Uuid\Uuid::fromString(self::NEW_NESTED_ACTION),
					'entity' => Tests\Fixtures\Dummy\DummyActionEntity::class,
					'doItem' => Uuid\Uuid::fromString(self::DO_ITEM),
					'value' => 'on',
				],
			],
			'notifications' => [
				[
					'id' => Uuid\Uuid::fromString(self::NEW_NESTED_NOTIFICATION),
					'entity' => Entities\Notifications\Email::class,
					'email' => 'reading@fastybird.com',
				],
			],
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$trigger = $fromArray['reloaded']['Triggers\Manual:' . self::NEW_TRIGGER];
		self::assertSame('Reading Time', $trigger['name']);
		self::assertSame(['scene' => ['brightness' => 40, 'colors' => ['warm', 'white']]], $trigger['params']);
		self::assertSame(['Dummy\DummyActionEntity:' . self::NEW_NESTED_ACTION], $trigger['actions']);
		self::assertSame(['Notifications\Email:' . self::NEW_NESTED_NOTIFICATION], $trigger['notifications']);
	}

	/**
	 * @throws Throwable
	 */
	public function testATriggerUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Triggers\TriggersManager::class);

		$values = static fn (): array => [
			'name' => 'Late Movie Night',
			'comment' => 'Changed',
			'enabled' => false,
			'params' => ['scene' => ['brightness' => 10]],
		];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$trigger = $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER);

			return [$manager->update($trigger, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$trigger = $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER);

			return [$manager->update($trigger, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$trigger = $fromArray['reloaded']['Triggers\Manual:' . self::MANUAL_TRIGGER];
		self::assertSame('Late Movie Night', $trigger['name']);
		self::assertFalse($trigger['enabled']);
		self::assertSame(['scene' => ['brightness' => 10]], $trigger['params']);
	}

	/**
	 * A control is created with its trigger; its name is not writable, so an update leaves it
	 *
	 * @throws Throwable
	 */
	public function testAControlCreatedAndUpdatedFromAnArrayIsTheOneAnArrayHashMakes(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsManager::class);

		$values = fn (): array => [
			'name' => 'reset',
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER),
		];

		$change = static fn (bool $asArrayHash): callable => static function () use ($manager, $values, $asArrayHash): array {
			$control = $manager->create(self::input($asArrayHash, $values()));

			return [$manager->update($control, self::input($asArrayHash, ['name' => 'renamed']))];
		};

		$fromArrayHash = $this->change($change(true));
		$fromArray = $this->change($change(false));

		self::assertSame($fromArrayHash, $fromArray);

		$control = $fromArray['reloaded']['Triggers\Controls\Control of ' . self::MANUAL_TRIGGER . '/reset'];
		self::assertSame('reset', $control['name']);
		self::assertSame('Triggers\Manual:' . self::MANUAL_TRIGGER, $control['trigger']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnActionCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Actions\ActionsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_ACTION),
			'entity' => Tests\Fixtures\Dummy\DummyActionEntity::class,
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER),
			'doItem' => Uuid\Uuid::fromString(self::DO_ITEM),
			'value' => 'off',
			'enabled' => false,
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$action = $fromArray['reloaded']['Dummy\DummyActionEntity:' . self::NEW_ACTION];
		self::assertSame('off', $action['value']);
		self::assertSame(self::DO_ITEM, $action['doItem']);
		self::assertFalse($action['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnActionUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Actions\ActionsManager::class);

		$values = static fn (): array => ['value' => 'off', 'enabled' => false];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$action = $this->find(Entities\Actions\Action::class, self::ACTION);

			return [$manager->update($action, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$action = $this->find(Entities\Actions\Action::class, self::ACTION);

			return [$manager->update($action, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$action = $fromArray['reloaded']['Dummy\DummyActionEntity:' . self::ACTION];
		self::assertSame('off', $action['value']);
		self::assertFalse($action['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConditionCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Conditions\ConditionsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_CONDITION),
			'entity' => Tests\Fixtures\Dummy\DummyConditionEntity::class,
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::AUTOMATIC_TRIGGER),
			'watchItem' => Uuid\Uuid::fromString(self::WATCH_ITEM),
			'operator' => Types\ConditionOperator::BELOW,
			'operand' => '21',
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$condition = $fromArray['reloaded']['Dummy\DummyConditionEntity:' . self::NEW_CONDITION];
		self::assertSame('21', $condition['operand']);
		self::assertSame('Triggers\Automatic:' . self::AUTOMATIC_TRIGGER, $condition['trigger']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConditionUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Conditions\ConditionsManager::class);

		$values = static fn (): array => [
			'operator' => Types\ConditionOperator::EQUAL,
			'operand' => '7',
			'enabled' => false,
		];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$condition = $this->find(Entities\Conditions\Condition::class, self::CONDITION);

			return [$manager->update($condition, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$condition = $this->find(Entities\Conditions\Condition::class, self::CONDITION);

			return [$manager->update($condition, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$condition = $fromArray['reloaded']['Dummy\DummyConditionEntity:' . self::CONDITION];
		self::assertSame('7', $condition['operand']);
		self::assertFalse($condition['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testANotificationCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_NOTIFICATION),
			'entity' => Entities\Notifications\Email::class,
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER),
			'email' => 'movies@fastybird.com',
			'enabled' => false,
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$notification = $fromArray['reloaded']['Notifications\Email:' . self::NEW_NOTIFICATION];
		self::assertSame('movies@fastybird.com', $notification['email']);
		self::assertFalse($notification['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testANotificationUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$values = static fn (): array => ['email' => 'jane.doe@fastybird.com', 'enabled' => false];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$notification = $this->find(Entities\Notifications\Notification::class, self::NOTIFICATION);

			return [$manager->update($notification, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$notification = $this->find(Entities\Notifications\Notification::class, self::NOTIFICATION);

			return [$manager->update($notification, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$notification = $fromArray['reloaded']['Notifications\Email:' . self::NOTIFICATION];
		self::assertSame('jane.doe@fastybird.com', $notification['email']);
		self::assertFalse($notification['enabled']);
	}

	/**
	 * Makes a change inside a transaction it rolls back, and records the graph reachable from the
	 * entities the change returns, as returned and as read back from the database
	 *
	 * @param callable(): list<object> $change
	 *
	 * @return array{returned: array<string, array<string, mixed>>, reloaded: array<string, array<string, mixed>>}
	 *
	 * @throws Throwable
	 */
	private function change(callable $change): array
	{
		$entityManager = $this->getEntityManager();
		$connection = $entityManager->getConnection();

		$connection->beginTransaction();

		try {
			$entities = $change();

			$returned = $this->dumpGraph($entities);

			$entityManager->clear();

			$reloaded = $this->dumpGraph(array_map(
				function (object $entity): object {
					assert($entity instanceof Entities\Entity);

					return $this->find($entity::class, $entity->getId()->toString());
				},
				$entities,
			));
		} finally {
			$connection->rollBack();
			$entityManager->clear();
		}

		return ['returned' => $returned, 'reloaded' => $reloaded];
	}

	/**
	 * @param list<object> $queue
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws Throwable
	 */
	private function dumpGraph(array $queue): array
	{
		$entityManager = $this->getEntityManager();

		$graph = [];

		while ($queue !== []) {
			$entity = array_shift($queue);

			$reference = $this->reference($entity);

			if (array_key_exists($reference, $graph)) {
				continue;
			}

			$metadata = $entityManager->getClassMetadata($entity::class);

			$record = [];

			foreach ($metadata->getFieldNames() as $field) {
				$record[$field] = $this->normalize($metadata->getFieldValue($entity, $field));
			}

			if ($entity instanceof Entities\Triggers\Controls\Control) {
				// Always generated, so the reference names a control instead
				unset($record['id']);
			}

			foreach ($metadata->getAssociationNames() as $association) {
				$value = $metadata->getFieldValue($entity, $association);

				if ($value instanceof Collection) {
					$references = [];

					foreach ($value as $item) {
						assert(is_object($item));

						$references[] = $this->reference($item);
						$queue[] = $item;
					}

					sort($references);

					$record[$association] = $references;
				} elseif (is_object($value)) {
					$record[$association] = $this->reference($value);
					$queue[] = $value;
				} else {
					$record[$association] = null;
				}
			}

			ksort($record);

			$graph[$reference] = $record;
		}

		ksort($graph);

		return $graph;
	}

	/**
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	private function find(string $class, string $id): object
	{
		$entity = $this->getEntityManager()->find($class, Uuid\Uuid::fromString($id));
		assert($entity instanceof $class);

		return $entity;
	}

	private function reference(object $entity): string
	{
		$class = str_replace(
			['FastyBird\\Module\\Triggers\\Tests\\Fixtures\\', 'FastyBird\\Module\\Triggers\\Entities\\'],
			'',
			$entity::class,
		);

		if ($entity instanceof Entities\Triggers\Controls\Control) {
			return sprintf('%s of %s/%s', $class, $entity->getTrigger()->getId()->toString(), $entity->getName());
		}

		assert($entity instanceof Entities\Entity);

		return sprintf('%s:%s', $class, $entity->getId()->toString());
	}

	/**
	 * The values as an ArrayHash, as the callers built them before #723, or as the plain array
	 *
	 * @param array<string, mixed> $values
	 *
	 * @return array<string, mixed>|Utils\ArrayHash<mixed>
	 */
	private static function input(bool $asArrayHash, array $values): array|Utils\ArrayHash
	{
		return $asArrayHash ? Utils\ArrayHash::from($values) : $values;
	}

	private function normalize(mixed $value): mixed
	{
		if ($value instanceof Utils\ArrayHash) {
			return ['ArrayHash' => $this->normalize((array) $value)];
		}

		if (is_array($value)) {
			return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
		}

		if ($value instanceof Uuid\UuidInterface) {
			return $value->toString();
		}

		if ($value instanceof BackedEnum) {
			return sprintf('%s::%s', $value::class, (string) $value->value);
		}

		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		}

		if (is_object($value)) {
			return sprintf('object:%s', $value::class);
		}

		return $value;
	}

}
