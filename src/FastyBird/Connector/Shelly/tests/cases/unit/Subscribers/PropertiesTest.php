<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Error;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Subscribers;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use Throwable;
use function array_replace;

/**
 * The state property a device gets, field by field: created with the device, made right when it is a dynamic property
 * with other fields, and replaced when it is not a dynamic property at all
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class PropertiesTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreatedWithTheDevice(): void
	{
		$this->createDevice();

		self::assertSame($this->expectedState(), $this->state());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testDynamicStateIsUpdated(): void
	{
		$device = $this->createDevice();

		$propertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesManager::class,
		);

		foreach ($device->getProperties() as $property) {
			$propertiesManager->update($property, [
				'name' => 'Kept',
				'dataType' => ValuesTypes\DataType::STRING,
				'unit' => 'x',
				'format' => null,
				'settable' => true,
				'queryable' => true,
			]);
		}

		$this->postPersist();

		self::assertSame(array_replace($this->expectedState(), ['name' => 'Kept']), $this->state());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testOtherStateIsReplaced(): void
	{
		$device = $this->createDevice();

		$propertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesManager::class,
		);

		foreach ($device->getProperties() as $property) {
			$propertiesManager->delete($property);
		}

		$propertiesManager->create([
			'entity' => DevicesEntities\Devices\Properties\Variable::class,
			'device' => $device,
			'identifier' => 'state',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'connected',
		]);

		$this->postPersist();

		self::assertSame($this->expectedState(), $this->state());
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expectedState(): array
	{
		return [
			'type' => 'dynamic',
			'category' => 'generic',
			'identifier' => 'state',
			'name' => 'State',
			'data_type' => 'enum',
			'unit' => null,
			'format' => [
				'connected',
				'disconnected',
				'alert',
				'lost',
				'unknown',
			],
			'invalid' => null,
			'scale' => null,
			'step' => null,
			'default' => null,
			'value_transformer' => null,
			'settable' => false,
			'queryable' => false,
			'children' => [],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function createDevice(): ShellyEntities\Devices\Device
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('shelly-local');

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, ShellyEntities\Connectors\Connector::class);

		self::assertInstanceOf(ShellyEntities\Connectors\Connector::class, $connector);

		$this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesManager::class)->create([
			'entity' => ShellyEntities\Devices\Device::class,
			'connector' => $connector,
			'identifier' => 'shelly-device',
		]);

		$this->getEntityManager()->clear();

		return $this->device();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function postPersist(): void
	{
		$this->getEntityManager()->clear();

		$this->getContainer()->getByType(Subscribers\Properties::class)->postPersist(
			new ORM\Event\PostPersistEventArgs($this->device(), $this->getEntityManager()),
		);

		$this->getEntityManager()->clear();
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function state(): array
	{
		$properties = $this->device()->getProperties();

		self::assertCount(1, $properties);

		return Tests\Support\StoredEntities::property($properties[0]);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): ShellyEntities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('shelly-device');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			ShellyEntities\Devices\Device::class,
		);

		self::assertInstanceOf(ShellyEntities\Devices\Device::class, $device);

		return $device;
	}

}
