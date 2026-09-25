<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Automator\DateTime\Hydrators;
use FastyBird\Automator\DateTime\Schemas;
use FastyBird\Automator\DateTime\Tests;
use FastyBird\Core\Exceptions;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DateTimeExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Schemas\Conditions\DateCondition::class, false));
		self::assertNotNull($container->getByType(Schemas\Conditions\TimeCondition::class, false));

		self::assertNotNull($container->getByType(Hydrators\Conditions\DataCondition::class, false));
		self::assertNotNull($container->getByType(Hydrators\Conditions\TimeCondition::class, false));
	}

}
