<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use Error;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use InvalidArgumentException;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React;
use React\EventLoop;
use RuntimeException;
use function array_diff;
use function sprintf;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testDiscover(): void
	{
		$subscribePromise = $this->createMock(React\Promise\PromiseInterface::class);
		$subscribePromise
			->method('then')
			->with(
				self::callback(static function (callable $callback): bool {
					$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, Entities\Devices\Bridge::BASE_TOPIC);
					$subscription = new NetMqtt\DefaultSubscription($topic);

					$callback($subscription);

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$publishPromise = $this->createMock(React\Promise\PromiseInterface::class);
		$publishPromise
			->method('then')
			->with(
				self::callback(static function (callable $callback): bool {
					$callback();

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$apiClient = $this->createMock(API\Client::class);
		$apiClient
			->method('subscribe')
			->willReturn($subscribePromise);
		$apiClient
			->method('publish')
			->willReturn($publishPromise);

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

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(0.1, static function () use ($apiClient): void {
			self::assertCount(1, $apiClient->onConnect);
			self::assertCount(1, $apiClient->onMessage);

			Utils\Arrays::invoke($apiClient->onConnect);
			Utils\Arrays::invoke(
				$apiClient->onMessage,
				new NetMqtt\DefaultMessage(
					'zigbee2mqtt/bridge/devices',
					Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/Messages/bridge_devices.json'),
				),
			);
		});

		$eventLoop->addTimer(1, static function () use ($eventLoop, $client): void {
			$client->disconnect();

			$eventLoop->stop();
		});

		$eventLoop->run();

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
