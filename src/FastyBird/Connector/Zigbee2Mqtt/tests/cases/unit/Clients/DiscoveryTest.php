<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Clients;

use Error;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use InvalidArgumentException;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React\EventLoop;
use RuntimeException;
use function array_diff;
use function array_map;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testDiscover(): void
	{
		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$broker = new Tests\Support\FakeBroker($eventLoop);

		$apiClient = new API\Client(
			'zigbee2mqtt-discovery-test',
			'127.0.0.1',
			$broker->getPort(),
			new Zigbee2Mqtt\Logger(),
			$eventLoop,
		);

		$connectionManager = $this->createMock(API\ConnectionManager::class);
		$connectionManager
			->method('getClient')
			->willReturn($apiClient);

		$this->mockContainerService(
			API\ConnectionManager::class,
			$connectionManager,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('zigbee2mqtt');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('zigbee2mqtt');

		$connectorDocument = $connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);
		self::assertInstanceOf(Documents\Connectors\Connector::class, $connectorDocument);

		self::assertEquals($connector->getId(), $connectorDocument->getId());

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connectorDocument);

		$client->discover()->then(static function () use ($broker): void {
			$broker->pushMessage(
				'zigbee2mqtt/bridge/devices',
				Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/Messages/bridge_devices.json'),
			);
		});

		$eventLoop->addTimer(1, static function () use ($eventLoop, $client): void {
			$client->disconnect();

			$eventLoop->stop();
		});

		$eventLoop->run();

		$broker->close();

		// Discovery reacted to the client connecting by asking the bridge to permit joining
		self::assertSame(
			['zigbee2mqtt/bridge/request/permit_join'],
			array_map(static fn (array $message): string => $message[0], $broker->getPublished()),
		);

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('0xa4c138f06eafa3da');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		self::assertInstanceOf(Entities\Devices\SubDevice::class, $device);

		$channelsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		);

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsRepository->findAllBy($findChannelsQuery, Entities\Channels\Channel::class);

		self::assertCount(6, $channels);

		$data = [];

		foreach ($channels as $channel) {
			$data[$channel->getIdentifier()] = $channel->getName();
		}

		$expected = [
			'numeric_voltage' => 'Voltage',
			'binary_occupancy' => 'Occupancy',
			'numeric_linkquality' => 'Linkquality',
			'numeric_battery' => 'Battery',
			'binary_tamper' => 'Tamper',
			'binary_battery_low' => 'Battery low',
		];

		self::assertEmpty(array_diff($expected, $data));
	}

}
