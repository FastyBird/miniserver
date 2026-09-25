<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Automator\DevicesModule\Hydrators;
use FastyBird\Automator\DevicesModule\Schemas;
use FastyBird\Automator\DevicesModule\Subscribers;
use FastyBird\Automator\DevicesModule\Tests;
use FastyBird\Core\Exceptions;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DevicesModuleExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Schemas\Actions\DevicePropertyAction::class, false));
		self::assertNotNull($container->getByType(Schemas\Actions\ChannelPropertyAction::class, false));
		self::assertNotNull($container->getByType(Schemas\Conditions\ChannelPropertyCondition::class, false));
		self::assertNotNull($container->getByType(Schemas\Conditions\DevicePropertyCondition::class, false));
		self::assertNotNull($container->getByType(Hydrators\Actions\DevicePropertyAction::class, false));
		self::assertNotNull($container->getByType(Hydrators\Actions\ChannelPropertyAction::class, false));
		self::assertNotNull($container->getByType(Hydrators\Conditions\ChannelPropertyCondition::class, false));
		self::assertNotNull($container->getByType(Hydrators\Conditions\DevicePropertyCondition::class, false));

		self::assertNotNull($container->getByType(Subscribers\ActionEntity::class, false));
		self::assertNotNull($container->getByType(Subscribers\ConditionEntity::class, false));
	}

}
