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
use TypeError;
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
 * Triggers' five entity managers take a plain array (#723).
 *
 * Each test makes one change from plain arrays, inside a transaction it rolls back, and checks the
 * result field by field: every mapped field and every association of the entities the change
 * reaches, as the manager returns them and again as read back from the database. The same
 * assertions passed against the code before #723, which built the same graph from an ArrayHash.
 * An ArrayHash is refused with a TypeError.
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
	public function testATriggerCreatedFromAnArray(): void
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

		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		$trigger = $fromArray['reloaded']['Triggers\Manual:' . self::NEW_TRIGGER];
		self::assertSame('Reading Time', $trigger['name']);
		self::assertSame(['scene' => ['brightness' => 40, 'colors' => ['warm', 'white']]], $trigger['params']);
		self::assertSame(['Dummy\DummyActionEntity:' . self::NEW_NESTED_ACTION], $trigger['actions']);
		self::assertSame(['Notifications\Email:' . self::NEW_NESTED_NOTIFICATION], $trigger['notifications']);
	}

	/**
	 * @throws Throwable
	 */
	public function testATriggerUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Triggers\TriggersManager::class);

		$values = static fn (): array => [
			'name' => 'Late Movie Night',
			'comment' => 'Changed',
			'enabled' => false,
			'params' => ['scene' => ['brightness' => 10]],
		];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$trigger = $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER);

			return [$manager->update($trigger, $values())];
		});

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
	public function testAControlCreatedAndUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsManager::class);

		$values = fn (): array => [
			'name' => 'reset',
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER),
		];

		$change = static function () use ($manager, $values): array {
			$control = $manager->create($values());

			return [$manager->update($control, ['name' => 'renamed'])];
		};

		$fromArray = $this->change($change);

		$control = $fromArray['reloaded']['Triggers\Controls\Control of ' . self::MANUAL_TRIGGER . '/reset'];
		self::assertSame('reset', $control['name']);
		self::assertSame('Triggers\Manual:' . self::MANUAL_TRIGGER, $control['trigger']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnActionCreatedFromAnArray(): void
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

		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		$action = $fromArray['reloaded']['Dummy\DummyActionEntity:' . self::NEW_ACTION];
		self::assertSame('off', $action['value']);
		self::assertSame(self::DO_ITEM, $action['doItem']);
		self::assertFalse($action['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnActionUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Actions\ActionsManager::class);

		$values = static fn (): array => ['value' => 'off', 'enabled' => false];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$action = $this->find(Entities\Actions\Action::class, self::ACTION);

			return [$manager->update($action, $values())];
		});

		$action = $fromArray['reloaded']['Dummy\DummyActionEntity:' . self::ACTION];
		self::assertSame('off', $action['value']);
		self::assertFalse($action['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConditionCreatedFromAnArray(): void
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

		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		$condition = $fromArray['reloaded']['Dummy\DummyConditionEntity:' . self::NEW_CONDITION];
		self::assertSame('21', $condition['operand']);
		self::assertSame('Triggers\Automatic:' . self::AUTOMATIC_TRIGGER, $condition['trigger']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConditionUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Conditions\ConditionsManager::class);

		$values = static fn (): array => [
			'operator' => Types\ConditionOperator::EQUAL,
			'operand' => '7',
			'enabled' => false,
		];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$condition = $this->find(Entities\Conditions\Condition::class, self::CONDITION);

			return [$manager->update($condition, $values())];
		});

		$condition = $fromArray['reloaded']['Dummy\DummyConditionEntity:' . self::CONDITION];
		self::assertSame('7', $condition['operand']);
		self::assertFalse($condition['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testANotificationCreatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_NOTIFICATION),
			'entity' => Entities\Notifications\Email::class,
			'trigger' => $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER),
			'email' => 'movies@fastybird.com',
			'enabled' => false,
		];

		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		$notification = $fromArray['reloaded']['Notifications\Email:' . self::NEW_NOTIFICATION];
		self::assertSame('movies@fastybird.com', $notification['email']);
		self::assertFalse($notification['enabled']);
	}

	/**
	 * @throws Throwable
	 */
	public function testANotificationUpdatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class);

		$values = static fn (): array => ['email' => 'jane.doe@fastybird.com', 'enabled' => false];

		$fromArray = $this->change(function () use ($manager, $values): array {
			$notification = $this->find(Entities\Notifications\Notification::class, self::NOTIFICATION);

			return [$manager->update($notification, $values())];
		});

		$notification = $fromArray['reloaded']['Notifications\Email:' . self::NOTIFICATION];
		self::assertSame('jane.doe@fastybird.com', $notification['email']);
		self::assertFalse($notification['enabled']);
	}

	/**
	 * The managers take an array only: an ArrayHash is refused with a TypeError before any entity
	 * is touched (#723), on create() and on update()
	 *
	 * @throws Throwable
	 */
	public function testTheManagersRefuseAnArrayHash(): void
	{
		$container = $this->getContainer();
		$hash = Utils\ArrayHash::from(['name' => 'Refused']);
		$entity = $this->find(Entities\Triggers\Trigger::class, self::MANUAL_TRIGGER);
		$triggers = $container->getByType(Models\Entities\Triggers\TriggersManager::class);
		$actions = $container->getByType(Models\Entities\Actions\ActionsManager::class);
		$conditions = $container->getByType(Models\Entities\Conditions\ConditionsManager::class);
		$notifications = $container->getByType(Models\Entities\Notifications\NotificationsManager::class);

		foreach (
			[
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $triggers->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $triggers->update($entity, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $actions->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $conditions->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $notifications->create($hash),
			] as $call
		) {
			self::assertTrue(self::refuses($call));
		}
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

	private function normalize(mixed $value): mixed
	{
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

	/**
	 * Whether a call is refused with a TypeError
	 *
	 * @param callable(): mixed $call
	 */
	private static function refuses(callable $call): bool
	{
		try {
			$call();
		} catch (TypeError) {
			return true;
		}

		return false;
	}

}
