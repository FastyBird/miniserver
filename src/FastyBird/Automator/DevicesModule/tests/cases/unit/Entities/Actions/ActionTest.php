<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\Entities\Actions;

use Error;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Automator\DevicesModule\Exceptions;
use FastyBird\Automator\DevicesModule\Queries;
use FastyBird\Automator\DevicesModule\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Triggers\Models as TriggersModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ActionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testValidation(): void
	{
		$repository = $this->getContainer()->getByType(TriggersModels\Entities\Actions\ActionsRepository::class);

		$findQuery = new Queries\Entities\FindActions();
		$findQuery->byId(Uuid\Uuid::fromString('4aa84028-d8b7-4128-95b2-295763634aa4'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertTrue($entity instanceof Entities\Actions\ChannelPropertyAction);

		self::assertTrue($entity->validate('on'));
		self::assertFalse($entity->validate('off'));
	}

}
