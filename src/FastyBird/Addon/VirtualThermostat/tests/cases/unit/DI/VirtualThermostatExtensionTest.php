<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Addon\VirtualThermostat\Commands;
use FastyBird\Addon\VirtualThermostat\Drivers;
use FastyBird\Addon\VirtualThermostat\Helpers;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Addon\VirtualThermostat\Tests;
use FastyBird\Core\Exceptions;
use Nette;

final class VirtualThermostatExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Schemas\Devices\Device::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Configuration::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Hydrators\Devices\Device::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Configuration::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Actors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Sensors::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Preset::class, false));

		self::assertNotNull($container->getByType(Drivers\ThermostatFactory::class, false));

		self::assertNotNull($container->getByType(Helpers\Device::class, false));

		self::assertNotNull($container->getByType(Commands\Install::class, false));
	}

}
