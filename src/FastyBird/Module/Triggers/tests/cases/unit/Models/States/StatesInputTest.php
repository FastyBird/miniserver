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

/**
 * Triggers' action and condition state managers take a plain array as well as an ArrayHash, and
 * so do the ActionsWriter and ConditionsWriter interfaces they hand it to (#723).
 *
 * The writers here stand for a state storage (the RedisDb bridge implements both interfaces) and
 * record what they are given. A manager hands its writer the values exactly as it gets them: the
 * array as the same array, an ArrayHash as the same object. Against the code before #723 the
 * plain array is a TypeError.
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

		$arrayHash = Utils\ArrayHash::from($values);

		self::assertSame($state, $manager->create($action, $values));
		self::assertSame($state, $manager->create($action, $arrayHash));
		self::assertSame($state, $manager->update($action, $state, $values));
		self::assertSame($state, $manager->update($action, $state, $arrayHash));

		self::assertSame(
			[
				['create', self::ACTION, $values],
				['create', self::ACTION, $arrayHash],
				['update', self::ACTION, $values],
				['update', self::ACTION, $arrayHash],
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

		$arrayHash = Utils\ArrayHash::from($values);

		self::assertSame($state, $manager->create($condition, $values));
		self::assertSame($state, $manager->create($condition, $arrayHash));
		self::assertSame($state, $manager->update($condition, $state, $values));
		self::assertSame($state, $manager->update($condition, $state, $arrayHash));

		self::assertSame(
			[
				['create', self::CONDITION, $values],
				['create', self::CONDITION, $arrayHash],
				['update', self::CONDITION, $values],
				['update', self::CONDITION, $arrayHash],
			],
			$writer->written,
		);
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

}
