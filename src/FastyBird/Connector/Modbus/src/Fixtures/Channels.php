<?php declare(strict_types = 1);

/**
 * Channels.php
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

/**
 * Devices channels database fixture
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Fixtures
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channels extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
{

	public function load(Persistence\ObjectManager $manager): void
	{
		$device = $this->getReference('modbus-rtu-device', Entities\Devices\Device::class);

		for ($i = 1; $i <= 4; $i++) {
			$channel = new Entities\Channels\Channel($device, 'channel-' . $i);

			$manager->persist($channel);

			$this->setReference('modbus-rtu-channel-' . $i, $channel);
		}

		$manager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDependencies(): array
	{
		return [
			Devices::class,
		];
	}

}
