<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Commands;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Hydrators;
use FastyBird\Module\Devices\Middleware;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Utilities;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DevicesModuleExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Connector::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Exchange::class, false));

		self::assertNotNull($this->getContainer()->getByType(Middleware\Access::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Devices\Controls\ControlsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Channels\ChannelsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Channels\Properties\PropertiesRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Channels\Controls\ControlsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Connectors\ConnectorsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Connectors\Controls\ControlsRepository::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Devices\Controls\ControlsManager::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Channels\ChannelsManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Channels\Properties\PropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Channels\Controls\ControlsManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Connectors\ConnectorsManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Connectors\Controls\ControlsManager::class, false),
		);

		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\Connectors\Repository::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\States\Devices\Repository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Channels\Repository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\States\Connectors\Manager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Connectors\Async\Manager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Devices\Manager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Devices\Async\Manager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Channels\Manager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\Channels\Async\Manager::class, false));

		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\ConnectorPropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\Async\ConnectorPropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\DevicePropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\Async\DevicePropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\ChannelPropertiesManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\States\Async\ChannelPropertiesManager::class, false),
		);

		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Connectors\Controls\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Devices\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Channels\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Channels\Properties\Repository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Configuration\Channels\Controls\Repository::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Controllers\ConnectorsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ConnectorPropertiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ConnectorPropertyStateV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ConnectorControlsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DevicesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DeviceChildrenV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DevicePropertiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DevicePropertyChildrenV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DevicePropertyStateV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\DeviceControlsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ChannelsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ChannelPropertiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ChannelPropertyChildrenV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ChannelPropertyStateV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ChannelControlsV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Properties\States\State::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Connectors\Controls\Control::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Properties\Mapped::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Properties\States\State::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Devices\Controls\Control::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Properties\Mapped::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Properties\States\State::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Channels\Controls\Control::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Connectors\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Devices\Properties\Mapped::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Properties\Dynamic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Properties\Variable::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Channels\Properties\Mapped::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\Validator::class, false));
		self::assertNotNull($this->getContainer()->getByType(Router\ApiRoutes::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\StateEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\ConfigurationCache::class, false));

		self::assertNotNull($this->getContainer()->getByType(Utilities\DeviceConnection::class, false));
		self::assertNotNull($this->getContainer()->getByType(Utilities\ConnectorConnection::class, false));
	}

}
