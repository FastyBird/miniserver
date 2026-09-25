<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Commands;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Controllers;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Router;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests;
use FastyBird\Core\Exceptions;
use Nette;

final class ShellyConnectorHomeKitConnectorExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Mapping\Builder::class, false));

		self::assertNotNull($container->getByType(Schemas\Channels\Lightbulb::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Outlet::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Relay::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Valve::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\WindowCovering::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\InputButton::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\InputSwitch::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Shelly::class, false));

		self::assertNotNull($container->getByType(Hydrators\Channels\Lightbulb::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Outlet::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Relay::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Valve::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\WindowCovering::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\InputButton::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\InputSwitch::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Shelly::class, false));

		self::assertNotNull($container->getByType(Router\ApiRoutes::class, false));
	}

}
