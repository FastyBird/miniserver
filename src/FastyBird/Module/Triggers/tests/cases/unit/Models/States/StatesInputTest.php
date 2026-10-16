<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Models\States;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Tests;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use TypeError;

/**
 * Triggers' action and condition state managers take a plain array, and so do the ActionsWriter
 * and ConditionsWriter interfaces they hand it to (#723).
 *
 * The writers here stand for a state storage (the RedisDb bridge implements both interfaces) and
 * record what they are given. A manager hands its writer the values exactly as it gets them. An
 * ArrayHash is refused with a TypeError.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StatesInputTest extends Tests\Cases\Unit\DbTestCase
{

	private const string ACTION = '46c39a95-39eb-4216-9fa3-4d575a6295bd';

	private const string CONDITION = '09c453b3-c55f-4050-8f1c-b50f8d5728c2';

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function values(): array
	{
		return [
			'flat' => [['triggered' => true]],
			'nested' => [['triggered' => false, 'detail' => ['reason' => ['code' => 3, 'items' => ['a', 'b']]]]],
			'null' => [['triggered' => null]],
			'empty' => [[]],
		];
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testAnActionStateIsWrittenFromTheValuesAsGiven(array $values): void
	{
		$writer = new Tests\Fixtures\Dummy\DummyActionsWriter();
		$manager = new Models\States\ActionsManager(
			$this->getContainer()->getByType(CoreDocuments\DocumentFactory::class),
			$writer,
		);

		$action = $this->find(Entities\Actions\Action::class, self::ACTION);
		$state = $writer->state($action->getId());

		self::assertSame($state, $manager->create($action, $values));
		self::assertSame($state, $manager->update($action, $state, $values));

		self::assertSame(
			[
				['create', self::ACTION, $values],
				['update', self::ACTION, $values],
			],
			$writer->written,
		);
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testAConditionStateIsWrittenFromTheValuesAsGiven(array $values): void
	{
		$writer = new Tests\Fixtures\Dummy\DummyConditionsWriter();
		$manager = new Models\States\ConditionsManager(
			$this->getContainer()->getByType(CoreDocuments\DocumentFactory::class),
			$writer,
		);

		$condition = $this->find(Entities\Conditions\Condition::class, self::CONDITION);
		$state = $writer->state($condition->getId());

		self::assertSame($state, $manager->create($condition, $values));
		self::assertSame($state, $manager->update($condition, $state, $values));

		self::assertSame(
			[
				['create', self::CONDITION, $values],
				['update', self::CONDITION, $values],
			],
			$writer->written,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testTheManagersRefuseAnArrayHash(): void
	{
		$writer = new Tests\Fixtures\Dummy\DummyActionsWriter();
		$conditionsWriter = new Tests\Fixtures\Dummy\DummyConditionsWriter();
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);
		$actions = new Models\States\ActionsManager($factory, $writer);
		$conditions = new Models\States\ConditionsManager($factory, $conditionsWriter);

		$action = $this->find(Entities\Actions\Action::class, self::ACTION);
		$condition = $this->find(Entities\Conditions\Condition::class, self::CONDITION);
		$hash = Utils\ArrayHash::from(['triggered' => true]);
		$actionState = $writer->state($action->getId());
		$conditionState = $conditionsWriter->state($condition->getId());

		foreach (
			[
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $actions->create($action, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $actions->update($action, $actionState, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $conditions->create($condition, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $conditions->update($condition, $conditionState, $hash),
			] as $call
		) {
			self::assertTrue(self::refuses($call));
		}

		self::assertSame([], $writer->written);
		self::assertSame([], $conditionsWriter->written);
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
		self::assertInstanceOf($class, $entity);

		return $entity;
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
