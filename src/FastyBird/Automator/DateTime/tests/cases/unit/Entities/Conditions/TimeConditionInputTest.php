<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Tests\Cases\Unit\Entities\Conditions;

use DateTime;
use DateTimeImmutable;
use FastyBird\Automator\DateTime\Entities as DateTimeEntities;
use FastyBird\Automator\DateTime\Exceptions as DateTimeExceptions;
use FastyBird\Automator\DateTime\Tests;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Models as TriggersModels;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use TypeError;

/**
 * A time condition takes its days as a plain array (#723).
 *
 * The constructor is reached through the Triggers conditions manager's entity creator, which hands
 * it the days as the array it was given; the first test calls it directly. An ArrayHash of days is
 * refused with a TypeError. The last test pins what happens without days.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class TimeConditionInputTest extends Tests\Cases\Unit\DbTestCase
{

	// Rise n'Shine, an automatic trigger in the fixtures
	private const string TRIGGER = '1b17bcaa-a19e-45f0-98b4-56211cc648ae';

	private const string CONDITION = 'f4c8e2a6-1b3d-4f5a-8c7e-9d0b1a2c3e01';

	/**
	 * @throws Throwable
	 */
	public function testATimeConditionTakesItsDaysAsAnArray(): void
	{
		$trigger = $this->trigger();

		$fromArray = new DateTimeEntities\Conditions\TimeCondition(
			new DateTimeImmutable('2020-01-01T07:30:00+00:00'),
			[1, 3, 5],
			$trigger,
			Uuid\Uuid::fromString(self::CONDITION),
		);

		self::assertSame([1, 3, 5], $fromArray->getDays());
		self::assertSame([1, 3, 5], $fromArray->toArray()['days']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayHashOfDaysIsRefused(): void
	{
		$this->expectException(TypeError::class);

		new DateTimeEntities\Conditions\TimeCondition(
			new DateTimeImmutable('2020-01-01T07:30:00+00:00'),
			// @phpstan-ignore argument.type (the call is the test: the days are an array)
			Utils\ArrayHash::from([1, 3, 5]),
			$this->trigger(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testDaysOutsideTheWeekAreRefusedAsAnArray(): void
	{
		$this->expectException(DateTimeExceptions\InvalidArgument::class);

		new DateTimeEntities\Conditions\TimeCondition(
			new DateTimeImmutable('2020-01-01T07:30:00+00:00'),
			[1, 8],
			$this->trigger(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testATimeConditionCreatedFromAnArray(): void
	{
		$manager = $this->getContainer()->getByType(TriggersModels\Entities\Conditions\ConditionsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::CONDITION),
			'entity' => DateTimeEntities\Conditions\TimeCondition::class,
			'trigger' => $this->trigger(),
			'time' => new DateTime('2020-01-01T21:15:00+00:00'),
			'days' => [6, 7],
			'enabled' => false,
		];

		$fromArray = $this->created(
			static fn (): TriggersEntities\Conditions\Condition => $manager->create($values()),
		);

		self::assertSame([6, 7], $fromArray['returned']['days']);
		self::assertSame([6, 7], $fromArray['reloaded']['days']);
		self::assertFalse($fromArray['reloaded']['enabled']);
	}

	/**
	 * Without days the constructor has nothing to take them from, so the entity is not created
	 *
	 * @throws Throwable
	 */
	public function testATimeConditionWithoutDaysIsNotCreated(): void
	{
		$manager = $this->getContainer()->getByType(TriggersModels\Entities\Conditions\ConditionsManager::class);

		$this->expectException(PersistenceExceptions\EntityCreation::class);

		$manager->create([
			'id' => Uuid\Uuid::fromString(self::CONDITION),
			'entity' => DateTimeEntities\Conditions\TimeCondition::class,
			'trigger' => $this->trigger(),
			'time' => new DateTime('2020-01-01T21:15:00+00:00'),
		]);
	}

	/**
	 * Creates a condition inside a transaction it rolls back, and records it as returned and as
	 * read back from the database
	 *
	 * @param callable(): TriggersEntities\Conditions\Condition $create
	 *
	 * @return array{returned: array<mixed>, reloaded: array<mixed>}
	 *
	 * @throws Throwable
	 */
	private function created(callable $create): array
	{
		$entityManager = $this->getEntityManager();
		$connection = $entityManager->getConnection();

		$connection->beginTransaction();

		try {
			$condition = $create();
			self::assertInstanceOf(DateTimeEntities\Conditions\TimeCondition::class, $condition);

			$returned = $condition->toArray();

			$entityManager->clear();

			$reloaded = $entityManager->find(
				DateTimeEntities\Conditions\TimeCondition::class,
				Uuid\Uuid::fromString(self::CONDITION),
			);
			self::assertInstanceOf(DateTimeEntities\Conditions\TimeCondition::class, $reloaded);

			$reloaded = $reloaded->toArray();
		} finally {
			$connection->rollBack();
			$entityManager->clear();
		}

		return ['returned' => $returned, 'reloaded' => $reloaded];
	}

	/**
	 * @throws Throwable
	 */
	private function trigger(): TriggersEntities\Triggers\Automatic
	{
		$trigger = $this->getEntityManager()->find(
			TriggersEntities\Triggers\Automatic::class,
			Uuid\Uuid::fromString(self::TRIGGER),
		);
		self::assertInstanceOf(TriggersEntities\Triggers\Automatic::class, $trigger);

		return $trigger;
	}

}
