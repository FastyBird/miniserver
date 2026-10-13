<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function assert;

/**
 * The connector has two devices, `abc` and `abcd`, and the identifier of the second extends the first: what is stored
 * for one must reach that one only
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreStateTest extends Tests\Cases\Unit\DbTestCase
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testConnectionStateReachesTheDeviceWithThatIdentifierOnly(): void
	{
		$devices = [];

		$connection = $this->mockOf(
			DevicesUtilities\DeviceConnection::class,
			static function (MockObject $mock) use (&$devices): void {
				$mock->method('getState')->willReturn(DevicesTypes\ConnectionState::UNKNOWN);
				$mock
					->method('setState')
					->willReturnCallback(
						static function (DevicesDocuments\Devices\Device $device) use (&$devices): bool {
							$devices[] = $device->getId()->toString();

							return true;
						},
					);
			},
		);

		$this->mockContainerService(DevicesUtilities\DeviceConnection::class, $connection);

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class);

		$consumer->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDeviceConnectionState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'abc',
					'state' => DevicesTypes\ConnectionState::CONNECTED,
				],
			),
		);

		self::assertSame([self::DEVICE_ABC], $devices);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testParameterStateReachesTheDeviceWithThatIdentifierOnly(): void
	{
		$devices = [];

		$states = $this->mockOf(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			static function (MockObject $mock) use (&$devices): void {
				$mock
					->method('set')
					->willReturnCallback(
						static function (DevicesDocuments\Devices\Properties\Dynamic $property) use (&$devices): Promise\PromiseInterface {
							$devices[] = $property->getDevice()->toString();

							return Promise\resolve(true);
						},
					);
			},
		);

		$this->mockContainerService(DevicesModels\States\Async\DevicePropertiesManager::class, $states);

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreParametersStates::class);

		$consumer->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreParametersStates::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'abc',
					'parameters' => [
						['name' => 'rssi', 'value' => -50],
					],
				],
			),
		);

		self::assertSame([self::DEVICE_ABC], $devices);
	}

	/**
	 * The services replaced are final, which PHPStan does not let a mock of one be typed as, so the mock is handed over
	 * as the class it stands in for
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 * @param Closure(MockObject): void $configure
	 *
	 * @return T
	 */
	private function mockOf(string $class, Closure $configure): object
	{
		$mock = $this->createMock($class);

		assert($mock instanceof $class);

		$configure($mock);

		return $mock;
	}

}
