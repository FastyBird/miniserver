<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Queue\Consumers;

use Error;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Throwable;
use function assert;
use function is_int;
use function is_string;

/**
 * A discovery run stores every device the cloud knows, with the address the search of the network found it at, or
 * with none when the search did not find it
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	private const string DEVICE_ABC = '1e4b1d2f-6e3c-4b9f-ad8e-7c6b5a4f3e2d';

	#[Override]
	public function setUp(): void
	{
		// The files load in the reverse order they are registered in, so this one comes after the connectors
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../../sql/identifiers.data.sql');

		parent::setUp();
	}

	/**
	 * A device the search of the network did not find keeps the address it was stored with, and a device it found
	 * takes the address it was found at
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testDeviceStoredWithoutAnAddressKeepsTheAddressItWasStoredWith(): void
	{
		$this->store('10.0.0.7', 'eWeLink_abc.local', 8_081);

		self::assertSame(['10.0.0.7', 'eWeLink_abc.local', 8_081], $this->storedAddress());

		$this->store(null, null, null);

		self::assertSame(['10.0.0.7', 'eWeLink_abc.local', 8_081], $this->storedAddress());

		$this->store('10.0.0.9', 'eWeLink_abc.local', 8_082);

		self::assertSame(['10.0.0.9', 'eWeLink_abc.local', 8_082], $this->storedAddress());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function store(string|null $ipAddress, string|null $domain, int|null $port): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'id' => 'abc',
					'apiKey' => 'api-key',
					'deviceKey' => 'device-key',
					'uiid' => 1,
					'name' => 'Shorter identifier',
					'description' => null,
					'brandName' => 'SONOFF',
					'brandLogo' => null,
					'productModel' => 'BASICR2',
					'model' => 'PSF-BD1-GL',
					'mac' => 'd0:27:00:00:00:01',
					'ipAddress' => $ipAddress,
					'domain' => $domain,
					'port' => $port,
					'parameters' => [],
				],
			),
		);

		self::assertTrue($consumed);
	}

	/**
	 * @return array{string|null, string|null, int|null}
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storedAddress(): array
	{
		$ipAddress = $this->storedValue(Types\DevicePropertyIdentifier::IP_ADDRESS);
		$domain = $this->storedValue(Types\DevicePropertyIdentifier::ADDRESS);
		$port = $this->storedValue(Types\DevicePropertyIdentifier::PORT);

		assert($ipAddress === null || is_string($ipAddress));
		assert($domain === null || is_string($domain));
		assert($port === null || is_int($port));

		return [$ipAddress, $domain, $port];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storedValue(Types\DevicePropertyIdentifier $identifier): mixed
	{
		$findPropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findPropertyQuery->byDeviceId(Uuid\Uuid::fromString(self::DEVICE_ABC));
		$findPropertyQuery->byIdentifier($identifier);

		return $this->getContainer()
			->getByType(DevicesModels\Entities\Devices\Properties\PropertiesRepository::class)
			->findOneBy($findPropertyQuery)
			?->getValue();
	}

}
