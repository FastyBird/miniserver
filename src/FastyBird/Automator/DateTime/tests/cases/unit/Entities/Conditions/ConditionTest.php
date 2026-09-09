<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Tests\Cases\Unit\Entities\Conditions;

use DateTime;
use Error;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Automator\DateTime\Exceptions;
use FastyBird\Automator\DateTime\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Module\Triggers\Queries as TriggersQueries;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConditionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testTimeConditionValidation(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Entities\Conditions\ConditionsRepository::class);

		$findQuery = new TriggersQueries\Entities\FindConditions();
		$findQuery->byId(Uuid\Uuid::fromString('09c453b3-c55f-4050-8f1c-b50f8d5728c2'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Conditions\TimeCondition);

		self::assertTrue($entity->validate(new DateTime('1970-01-01T07:30:00+00:00')));
		self::assertTrue($entity->validate(new DateTime('07:30:00+00:00')));
		self::assertTrue($entity->validate(new DateTime('07:30:00')));

		self::assertFalse($entity->validate(new DateTime('1970-01-01T07:31:00+00:00')));
		self::assertFalse($entity->validate(new DateTime('07:31:00+00:00')));
		self::assertFalse($entity->validate(new DateTime('07:31:00')));
	}

}
