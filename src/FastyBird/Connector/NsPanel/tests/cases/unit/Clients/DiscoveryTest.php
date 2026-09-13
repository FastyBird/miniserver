<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Clients;

use Error;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Types;
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
use React\EventLoop;
use RuntimeException;

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
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 */
	public function testDiscover(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/responses/get_sub_devices.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$responsePromise = $this->createMock(React\Promise\PromiseInterface::class);
		$responsePromise
			->method('then')
			->with(
				self::callback(static function (callable $callback) use ($response): bool {
					$callback($response);

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$httpClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpClient
			->method('send')
			->willReturn($responsePromise);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

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

		$findDeviceQuery = new Queries\Configuration\FindSubDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('a480062416');

		$device = $devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\SubDevice::class,
		);

		$deviceHelper = $this->getContainer()->getByType(Helpers\Devices\SubDevice::class);

		self::assertInstanceOf(Documents\Devices\SubDevice::class, $device);
		self::assertSame(
			Types\Category::TEMPERATURE_HUMIDITY_SENSOR,
			$deviceHelper->getDisplayCategory($device),
		);
		self::assertSame('eWeLink', $deviceHelper->getManufacturer($device));
		self::assertSame('TH01', $deviceHelper->getModel($device));

		$channelsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Channels\Repository::class,
		);

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsConfigurationRepository->findAllBy(
			$findChannelsQuery,
			Documents\Channels\Channel::class,
		);

		$channelHelper = $this->getContainer()->getByType(Helpers\Channels\Channel::class);

		self::assertCount(4, $channels);

		foreach ($channels as $channel) {
			self::assertContains(
				$channelHelper->getCapability($channel),
				[
					Types\Capability::TEMPERATURE,
					Types\Capability::HUMIDITY,
					Types\Capability::BATTERY,
					Types\Capability::RSSI,
				],
			);

			self::assertCount(1, $channel->getProperties());
		}
	}

}
