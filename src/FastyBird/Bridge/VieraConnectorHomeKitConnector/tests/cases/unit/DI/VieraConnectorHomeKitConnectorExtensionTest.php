<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Commands;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Controllers;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Router;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests;
use FastyBird\Core\Exceptions;
use Nette;

final class VieraConnectorHomeKitConnectorExtensionTest extends Tests\Cases\Unit\BaseTestCase
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

		self::assertNotNull($container->getByType(Schemas\Channels\Television::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\TelevisionSpeaker::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\InputSource::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Viera::class, false));

		self::assertNotNull($container->getByType(Hydrators\Channels\Television::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\TelevisionSpeaker::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\InputSource::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Viera::class, false));

		self::assertNotNull($container->getByType(Router\ApiRoutes::class, false));
	}

}
