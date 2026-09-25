<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests;
use FastyBird\Core\Exceptions;
use Nette;

final class VirtualThermostatAddonHomeKitConnectorExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Builders\Builder::class, false));

		self::assertNotNull($container->getByType(Commands\Build::class, false));

		self::assertNotNull($container->getByType(Controllers\BridgesV1::class, false));

		self::assertNotNull($container->getByType(Schemas\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Thermostat::class, false));

		self::assertNotNull($container->getByType(Hydrators\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Thermostat::class, false));

		self::assertNotNull($container->getByType(Router\ApiRoutes::class, false));
	}

}
