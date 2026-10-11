<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Doctrine\DBAL;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionException;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DeviceNameCharsetTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * A 4-byte UTF-8 character, here an emoji, survives a persisted entity's string column:
	 * written through the entity manager, read back from the database after the identity map
	 * is cleared, and present as the same bytes in the column itself.
	 *
	 * It needs `charset: utf8mb4` on the connection. With `charset: utf8`, which MariaDB reads
	 * as utf8mb3, the character cannot travel to the server at all, and the insert fails (#444).
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws ReflectionException
	 */
	public function testFourByteCharacterRoundTripsThroughAnEntityName(): void
	{
		$name = "Kitchen lamp \u{1F4A1}";

		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);

		$device = $manager->create(Utils\ArrayHash::from([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'identifier' => 'emoji-device',
			'connector' => $parent->getConnector(),
			'name' => $name,
		]));

		$this->getEntityManager()->clear();

		$reloaded = $repository->find($device->getId());

		self::assertIsObject($reloaded);
		self::assertNotSame($device, $reloaded);
		self::assertSame($name, $reloaded->getName());

		self::assertSame(
			$name,
			$this->getDb()->fetchOne(
				'SELECT device_name FROM fb_devices_module_devices WHERE device_identifier = ?',
				['emoji-device'],
			),
		);
	}

}
