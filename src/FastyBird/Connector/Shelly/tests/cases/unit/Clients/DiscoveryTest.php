<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Clients;

use Error;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Http;
use React;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use function is_string;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 */
	public function testDiscoverGen1LocalDevices(): void
	{
		$multicastFactoryService = $this->createMock(Services\MulticastFactory::class);
		$multicastFactoryService
			->method('create')
			->willReturnCallback(
				function (string $address, int $port): Datagram\SocketInterface {
					$datagramClient = $this->createMock(Datagram\Socket::class);
					$datagramClient
						->method('on')
						->willReturnCallback(
							static function (string $eventName, callable $callback) use ($address, $port): void {
								if ($address === '224.0.0.251' && $port === 5_353) {
									$callback(
										Utils\FileSystem::read(
											__DIR__ . '/../../../fixtures/Clients/gen1_shelly_rgbw.mdns',
										),
									);
								}
							},
						);
					$datagramClient
						->method('send')
						->with(
							self::callback(static function ($data): bool {
								self::assertTrue(is_string($data));

								return true;
							}),
							self::callback(static function (string $remoteAddress): bool {
								self::assertSame('224.0.0.251:5353', $remoteAddress);

								return true;
							}),
						);
					$datagramClient
						->method('close');

					return $datagramClient;
				},
			);

		$this->mockContainerService(Services\MulticastFactory::class, $multicastFactoryService);

		$httpAsyncClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpAsyncClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Promise\PromiseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					$responsePromise = $this->createMock(Promise\PromiseInterface::class);
					$responsePromise
						->method('then')
						->with(
							self::callback(static function (callable $callback) use ($response): bool {
								$callback($response);

								return true;
							}),
							self::callback(static fn (): bool => true),
						);

					if (strval($request->getUri()) === 'http://10.10.0.239/shelly') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen1_local_discovery_device_info.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.239/cit/d') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen1_local_discovery_device_state.json',
								),
							);

					} else {
						throw new Exceptions\InvalidState(
							'This api call should not occur: ' . strval($request->getUri()),
						);
					}

					$responseBody
						->method('getContents')
						->willReturn(
							'',
						);

					return $responsePromise;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpAsyncClient) {
					if ($async) {
						return $httpAsyncClient;
					}

					throw new Exceptions\InvalidState('Sync clients should not be called when doing devices discovery');
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('shelly-local');

		$connector = $connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);
		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(1, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Devices\Repository::class,
		);

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('c45bbee4c926-shellyrgbw2');

		$device = $devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		$deviceHelper = $this->getContainer()->getByType(Helpers\Device::class);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame(Types\DeviceGeneration::GENERATION_1, $deviceHelper->getGeneration($device));
		self::assertSame('10.10.0.239', $deviceHelper->getIpAddress($device));
		self::assertSame('shellyrgbw2-C45BBEE4C926.local', $deviceHelper->getDomain($device));
		self::assertFalse($deviceHelper->hasAuthentication($device));
		self::assertSame('SHRGBW2', $deviceHelper->getModel($device));
		self::assertSame('c4:5b:be:e4:c9:26', $deviceHelper->getMacAddress($device));

		$channelsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Channels\Repository::class,
		);

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsConfigurationRepository->findAllBy(
			$findChannelsQuery,
			Documents\Channels\Channel::class,
		);

		self::assertCount(5, $channels);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 */
	public function testDiscoverGen2LocalDevices(): void
	{
		$multicastFactoryService = $this->createMock(Services\MulticastFactory::class);
		$multicastFactoryService
			->method('create')
			->willReturnCallback(
				function (string $address, int $port): Datagram\SocketInterface {
					$datagramClient = $this->createMock(Datagram\Socket::class);
					$datagramClient
						->method('on')
						->willReturnCallback(
							static function (string $eventName, callable $callback) use ($address, $port): void {
								if ($address === '224.0.0.251' && $port === 5_353) {
									$callback(
										Utils\FileSystem::read(
											__DIR__ . '/../../../fixtures/Clients/gen2_shelly_2pm_plus.mdns',
										),
									);
								}
							},
						);
					$datagramClient
						->method('send')
						->with(
							self::callback(static function ($data): bool {
								self::assertTrue(is_string($data));

								return true;
							}),
							self::callback(static function (string $remoteAddress): bool {
								self::assertSame('224.0.0.251:5353', $remoteAddress);

								return true;
							}),
						);
					$datagramClient
						->method('close');

					return $datagramClient;
				},
			);

		$this->mockContainerService(Services\MulticastFactory::class, $multicastFactoryService);

		$httpAsyncClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpAsyncClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Promise\PromiseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					$responsePromise = $this->createMock(Promise\PromiseInterface::class);
					$responsePromise
						->method('then')
						->with(
							self::callback(static function (callable $callback) use ($response): bool {
								$callback($response);

								return true;
							}),
							self::callback(static fn (): bool => true),
						);

					if (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetDeviceInfo') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_info.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetConfig') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_configuration.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetStatus') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_state.json',
								),
							);

					} else {
						throw new Exceptions\InvalidState(
							'This api call should not occur: ' . strval($request->getUri()),
						);
					}

					$responseBody
						->method('getContents')
						->willReturn(
							'',
						);

					return $responsePromise;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpAsyncClient) {
					if ($async) {
						return $httpAsyncClient;
					}

					throw new Exceptions\InvalidState('Sync clients should not be called when doing devices discovery');
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('shelly-local');

		$connector = $connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);
		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(1, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Devices\Repository::class,
		);

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('441793ad07e8-shellyplus2pm');

		$device = $devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		$deviceHelper = $this->getContainer()->getByType(Helpers\Device::class);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame(Types\DeviceGeneration::GENERATION_2, $deviceHelper->getGeneration($device));
		self::assertSame('10.10.0.37', $deviceHelper->getIpAddress($device));
		self::assertSame('ShellyPlus2PM-441793AD07E8.local', $deviceHelper->getDomain($device));
		self::assertFalse($deviceHelper->hasAuthentication($device));
		self::assertSame('SNSW-102P16EU', $deviceHelper->getModel($device));
		self::assertSame('44:17:93:ad:07:e8', $deviceHelper->getMacAddress($device));

		$channelsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Channels\Repository::class,
		);

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsConfigurationRepository->findAllBy(
			$findChannelsQuery,
			Documents\Channels\Channel::class,
		);

		self::assertCount(4, $channels);
	}

}
