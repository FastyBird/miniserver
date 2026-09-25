<?php declare(strict_types = 1);

/**
 * ChannelsProperties.php
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
use FastyBird\Connector\Modbus\Types\ChannelPropertyIdentifier;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use TypeError;
use ValueError;
use function strval;

/**
 * Channels properties database fixture
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelsProperties extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
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
		for ($i = 1; $i <= 4; $i++) {
			$channel = $this->getReference('modbus-rtu-channel-' . $i, Entities\Channels\Channel::class);

			$addressProperty = new DevicesEntities\Channels\Properties\Variable(
				$channel,
				ChannelPropertyIdentifier::ADDRESS->value,
			);
			$addressProperty->setDataType(Types\DataType::UINT);
			$addressProperty->setValue(strval($i));

			$switchProperty = new DevicesEntities\Channels\Properties\Dynamic(
				$channel,
				'switch',
			);
			$switchProperty->setDataType(Types\DataType::SWITCH);
			$switchProperty->setSettable(true);
			$switchProperty->setQueryable(true);
			$switchProperty->setFormat(
				'sw|switch_on:u8|1:u16|256,sw|switch_off:u8|0:u16|512,sw|switch_toggle::u16|768',
			);

			$manager->persist($addressProperty);
			$manager->persist($switchProperty);
		}

		$manager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDependencies(): array
	{
		return [
			Channels::class,
		];
	}

}
