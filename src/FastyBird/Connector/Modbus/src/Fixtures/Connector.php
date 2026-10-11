<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Fixtures;

use BadMethodCallException;
use Doctrine\Common\DataFixtures;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use Override;

/**
 * Connector database fixture
 */
final class Connector extends DataFixtures\AbstractFixture implements DataFixtures\FixtureInterface
{

	/**
	 * @throws BadMethodCallException
	 */
	#[Override]
	public function load(Persistence\ObjectManager $manager): void
	{
		$connector = new Entities\Connectors\Connector('modbus-rtu');
		$connector->setName('Modbus RTU');

		$manager->persist($connector);
		$manager->flush();

		$this->addReference('modbus-rtu-connector', $connector);
	}

}
