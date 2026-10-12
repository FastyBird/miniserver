<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Servers;

use Error;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Events as HomeKitEvents;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Tests;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Monolog;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * The mDNS server listens for `AccessoryConfigurationChanged` from `initialize()` until `disconnect()`.
 * It used to append a closure to `Subscribers\Entities::$onRefresh`, which it never removed.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class MdnsTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = 'f5a8691b-4917-4866-878f-5217193cf14b';

	private const string OTHER_CONNECTOR_ID = 'd0b4a1c5-46fa-4d1e-8bd4-0b4c5b5e0d0e';

	private const string REFRESHING = 'Connector configuration changed. Refreshing mDNS broadcast';

	private EventDispatcher\EventDispatcherInterface $dispatcher;

	private Monolog\Handler\TestHandler $logs;

	private Servers\Mdns $server;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function setUp(): void
	{
		// The zone the server builds needs the connector's MAC address and setup ID
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/mdns.data.sql');

		parent::setUp();

		$this->logs = new Monolog\Handler\TestHandler();

		$this->mockContainerService(
			HomeKit\Logger::class,
			new HomeKit\Logger(new Monolog\Logger('test', [$this->logs])),
		);

		$this->dispatcher = $this->getContainer()->getByType(EventDispatcher\EventDispatcherInterface::class);

		$repository = $this->getContainer()->getByType(DevicesModels\Configuration\Connectors\Repository::class);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString(self::CONNECTOR_ID));

		$connector = $repository->findOneBy($findConnectorQuery, Documents\Connectors\Connector::class);
		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);

		$this->server = $this->getContainer()->getByType(Servers\MdnsFactory::class)->create($connector);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testRebuildsZoneOnConfigurationChangeWhileStarted(): void
	{
		$this->server->initialize();

		self::assertCount(1, $this->dispatcher->getListeners(HomeKitEvents\AccessoryConfigurationChanged::class));

		$this->dispatcher->dispatch(
			new HomeKitEvents\AccessoryConfigurationChanged($this->createProperty(self::CONNECTOR_ID)),
		);

		self::assertTrue($this->logs->hasDebugThatContains(self::REFRESHING));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testIgnoresOtherConnector(): void
	{
		$this->server->initialize();

		$this->dispatcher->dispatch(
			new HomeKitEvents\AccessoryConfigurationChanged($this->createProperty(self::OTHER_CONNECTOR_ID)),
		);

		self::assertFalse($this->logs->hasDebugThatContains(self::REFRESHING));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testInitializeRegistersListenerOnce(): void
	{
		$this->server->initialize();
		$this->server->initialize();

		self::assertCount(1, $this->dispatcher->getListeners(HomeKitEvents\AccessoryConfigurationChanged::class));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testStopsReactingAfterDisconnect(): void
	{
		$this->server->initialize();
		$this->server->disconnect();

		self::assertFalse($this->dispatcher->hasListeners(HomeKitEvents\AccessoryConfigurationChanged::class));

		$this->dispatcher->dispatch(
			new HomeKitEvents\AccessoryConfigurationChanged($this->createProperty(self::CONNECTOR_ID)),
		);

		self::assertFalse($this->logs->hasDebugThatContains(self::REFRESHING));
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function createProperty(string $connectorId): DevicesEntities\Connectors\Properties\Variable
	{
		$connector = $this->createMock(DevicesEntities\Connectors\Connector::class);
		$connector
			->method('getId')
			->willReturn(Uuid\Uuid::fromString($connectorId));

		$property = $this->createMock(DevicesEntities\Connectors\Properties\Variable::class);
		$property
			->method('getConnector')
			->willReturn($connector);
		$property
			->method('getIdentifier')
			->willReturn(Types\ConnectorPropertyIdentifier::PAIRED->value);

		return $property;
	}

}
