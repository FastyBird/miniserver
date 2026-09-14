<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testBuild(): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('homekit');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);

		$findThermostatQuery = new DevicesQueries\Entities\FindDevices();
		$findThermostatQuery->byIdentifier('thermostat-office');

		$thermostat = $devicesRepository->findOneBy($findThermostatQuery);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);
		self::assertInstanceOf(VirtualThermostatEntities\Devices\Device::class, $thermostat);

		$bridge = $builder->build($thermostat, $connector);

		self::assertSame($thermostat, $bridge->getParent());

		self::assertCount(1, $bridge->getChannels());
		self::assertInstanceOf(Entities\Channels\Thermostat::class, $bridge->getChannels()[0]);

		$actual = [];

		foreach ($bridge->getChannels() as $channel) {
			$actual[$channel->getIdentifier()] = [];

			foreach ($channel->getProperties() as $property) {
				$actual[$channel->getIdentifier()][$property->getIdentifier()] = $property::class;
			}
		}

		self::assertSame([
			'thermostat_1' => [
				HomeKitTypes\ChannelPropertyIdentifier::CURRENT_HEATING_COOLING_STATE->value => DevicesEntities\Channels\Properties\Mapped::class,
				HomeKitTypes\ChannelPropertyIdentifier::TARGET_HEATING_COOLING_STATE->value => DevicesEntities\Channels\Properties\Mapped::class,
				HomeKitTypes\ChannelPropertyIdentifier::CURRENT_TEMPERATURE->value => DevicesEntities\Channels\Properties\Mapped::class,
				HomeKitTypes\ChannelPropertyIdentifier::TARGET_TEMPERATURE->value => DevicesEntities\Channels\Properties\Mapped::class,

				HomeKitTypes\ChannelPropertyIdentifier::TEMPERATURE_DISPLAY_UNITS->value => DevicesEntities\Channels\Properties\Dynamic::class,

				HomeKitTypes\ChannelPropertyIdentifier::NAME->value => DevicesEntities\Channels\Properties\Variable::class,
			],
		], $actual);
	}

}
