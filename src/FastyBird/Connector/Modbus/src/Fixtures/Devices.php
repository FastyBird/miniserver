<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Fixtures;

use BadMethodCallException;
use Doctrine\Common\DataFixtures;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use Override;

/**
 * Connector devices database fixture
 */
final class Devices extends DataFixtures\AbstractFixture implements DataFixtures\DependentFixtureInterface
{

	/**
	 * @throws BadMethodCallException
	 */
	#[Override]
	public function load(Persistence\ObjectManager $manager): void
	{
		$connector = $this->getReference('modbus-rtu-connector', Entities\Connectors\Connector::class);

		$device = new Entities\Devices\Device(
			'fixture-device',
			$connector,
			'Fixture device',
		);

		$manager->persist($device);
		$manager->flush();

		$this->addReference('modbus-rtu-device', $device);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getDependencies(): array
	{
		return [
			Connector::class,
		];
	}

}
