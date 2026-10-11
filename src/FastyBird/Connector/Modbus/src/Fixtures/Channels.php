<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Fixtures;

use Doctrine\Common\DataFixtures;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use Override;

/**
 * Devices channels database fixture
 */
final class Channels extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
{

	#[Override]
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
	#[Override]
	public function getDependencies(): array
	{
		return [
			Devices::class,
		];
	}

}
