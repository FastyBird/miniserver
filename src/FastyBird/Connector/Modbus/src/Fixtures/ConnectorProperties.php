<?php declare(strict_types = 1);

/**
 * ConnectorProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 * @since          1.0.0
 *
 * @date           22.08.22
 */

namespace FastyBird\Connector\Modbus\Fixtures;

use Doctrine\Common\DataFixtures;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions as ModbusExceptions;
use FastyBird\Connector\Modbus\Types as ModbusTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use TypeError;
use ValueError;

/**
 * Connector properties database fixture
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorProperties extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
{

	/**
	 * @throws ModbusExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function load(Persistence\ObjectManager $manager): void
	{
		$connector = $this->getReference('modbus-rtu-connector', Entities\Connectors\Connector::class);

		$clientModeProperty = new DevicesEntities\Connectors\Properties\Variable(
			$connector,
			ModbusTypes\ConnectorPropertyIdentifier::CLIENT_MODE->value,
		);
		$clientModeProperty->setDataType(ValuesTypes\DataType::STRING);
		$clientModeProperty->setValue(ModbusTypes\ClientMode::RTU->value);

		$interfaceProperty = new DevicesEntities\Connectors\Properties\Variable(
			$connector,
			ModbusTypes\ConnectorPropertyIdentifier::RTU_INTERFACE->value,
		);
		$interfaceProperty->setDataType(ValuesTypes\DataType::STRING);
		$interfaceProperty->setValue('/dev/ttyUSB0');

		$manager->persist($clientModeProperty);
		$manager->persist($interfaceProperty);
		$manager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDependencies(): array
	{
		return [
			Connector::class,
		];
	}

}
