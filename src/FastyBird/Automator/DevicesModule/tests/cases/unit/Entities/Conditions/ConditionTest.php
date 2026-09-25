<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\Entities\Conditions;

use Error;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Automator\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\Automator\DevicesModule\Queries;
use FastyBird\Automator\DevicesModule\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Triggers\Models as TriggersModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConditionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleExceptions\InvalidArgument
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testPropertyConditionValidation(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Entities\Conditions\ConditionsRepository::class);

		$findQuery = new Queries\Entities\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('2726f19c-7759-440e-b6f5-8c3306692fa2'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\ChannelPropertyCondition);

		self::assertTrue($entity->validate('3'));
		self::assertFalse($entity->validate('1'));
	}

}
