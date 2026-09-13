<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\API;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Http;
use RuntimeException;
use function is_array;
use function str_replace;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class LanApiTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testGetGatewayInfo(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/get_gateway_info.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('GET', $request->getMethod());
					self::assertSame('http://127.0.0.1:8081/open-api/v1/rest/bridge', strval($request->getUri()));
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
						],
						$request->getHeaders(),
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->getGatewayInfo(
			'127.0.0.1',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertSame('127.0.0.1', $response->getData()->getIpAddress());

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/get_gateway_info.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testGetGatewayAccessToken(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/get_access_token.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('GET', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/bridge/access_token?app_name=ns-panel',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
						],
						$request->getHeaders(),
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->getGatewayAccessToken(
			$connector->getIdentifier(),
			'127.0.0.1',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertSame('fbfccc3e-59d5-4aae-87c2-aed8a6b043ac', $response->getData()->getAccessToken());

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/get_access_token.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetGatewayAccessTokenError(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(
				Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/get_access_token_error.json'),
			);

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('GET', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/bridge/access_token?app_name=ns-panel',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
						],
						$request->getHeaders(),
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$this->expectException(Exceptions\LanApiCall::class);
		$this->expectExceptionMessage('Getting gateway access token failed: link button not pressed');

		$lanApi->getGatewayAccessToken(
			$connector->getIdentifier(),
			'127.0.0.1',
			API\LanApi::GATEWAY_PORT,
			false,
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testSynchroniseDevices(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/synchronise_devices.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('POST', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/thirdparty/event',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
							'Authorization' => [
								'Bearer abcdefghijklmnopqrstuvwxyz',
							],
						],
						$request->getHeaders(),
					);

					$actual = Utils\Json::decode($request->getBody()->getContents(), forceArrays: true);
					self::assertTrue(is_array($actual));

					$request->getBody()->rewind();

					Tests\Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/API/request/synchronise_devices.json',
						$request->getBody()->getContents(),
						static function (string $expectation) use ($actual): string {
							if (
								isset($actual['event'])
								&& is_array($actual['event'])
								&& isset($actual['event']['header'])
								&& is_array($actual['event']['header'])
								&& isset($actual['event']['header']['message_id'])
							) {
								$expectation = str_replace(
									'__MESSAGE_ID__',
									strval($actual['event']['header']['message_id']),
									$expectation,
								);
							}

							return $expectation;
						},
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->synchroniseDevices(
			[
				[
					'third_serial_number' => 'dfb92f3d-7a92-4a66-84c1-c903e89b13e7',
					'name' => 'Test Switch',
					'display_category' => 'switch',
					'capabilities' => [
						[
							'capability' => 'power',
							'permission' => 'readWrite',
						],
						[
							'capability' => 'rssi',
							'permission' => 'read',
						],
					],
					'state' => [
						'power' => [
							'powerState' => 'off',
						],
						'rssi' => [
							'rssi' => -51,
						],
					],
					'tags' => [],
					'manufacturer' => 'Custom manufacturer',
					'model' => 'Test model',
					'firmware_version' => '4.2',
					'service_address' => 'http://10.10.0.141/webhook',
					'online' => true,
				],
			],
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertCount(1, $response->getPayload()->getEndpoints());

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/synchronise_devices.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testReportDeviceState(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(
				Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/report_device_state.json'),
			);

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('POST', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/thirdparty/event',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
							'Authorization' => [
								'Bearer abcdefghijklmnopqrstuvwxyz',
							],
						],
						$request->getHeaders(),
					);

					$actual = Utils\Json::decode($request->getBody()->getContents(), forceArrays: true);
					self::assertTrue(is_array($actual));

					$request->getBody()->rewind();

					Tests\Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/API/request/report_device_state.json',
						$request->getBody()->getContents(),
						static function (string $expectation) use ($actual): string {
							if (
								isset($actual['event'])
								&& is_array($actual['event'])
								&& isset($actual['event']['header'])
								&& is_array($actual['event']['header'])
								&& isset($actual['event']['header']['message_id'])
							) {
								$expectation = str_replace(
									'__MESSAGE_ID__',
									strval($actual['event']['header']['message_id']),
									$expectation,
								);
							}

							return $expectation;
						},
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->reportDeviceState(
			'3f89afee-146d-4a7f-ba55-bf2f6bcc862c',
			[
				'power' => [
					'powerState' => 'on',
				],
			],
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/report_device_state.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testReportDeviceOnline(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(
				Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/report_device_online.json'),
			);

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('POST', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/thirdparty/event',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
							'Authorization' => [
								'Bearer abcdefghijklmnopqrstuvwxyz',
							],
						],
						$request->getHeaders(),
					);

					$actual = Utils\Json::decode($request->getBody()->getContents(), forceArrays: true);
					self::assertTrue(is_array($actual));

					$request->getBody()->rewind();

					Tests\Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/API/request/report_device_online.json',
						$request->getBody()->getContents(),
						static function (string $expectation) use ($actual): string {
							if (
								isset($actual['event'])
								&& is_array($actual['event'])
								&& isset($actual['event']['header'])
								&& is_array($actual['event']['header'])
								&& isset($actual['event']['header']['message_id'])
							) {
								$expectation = str_replace(
									'__MESSAGE_ID__',
									strval($actual['event']['header']['message_id']),
									$expectation,
								);
							}

							return $expectation;
						},
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->reportDeviceOnline(
			'3f89afee-146d-4a7f-ba55-bf2f6bcc862c',
			false,
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/report_device_online.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testGetSubDevices(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/get_sub_devices.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('GET', $request->getMethod());
					self::assertSame('http://127.0.0.1:8081/open-api/v1/rest/devices', strval($request->getUri()));
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
							'Authorization' => [
								'Bearer abcdefghijklmnopqrstuvwxyz',
							],
						],
						$request->getHeaders(),
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->getSubDevices(
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertCount(2, $response->getData()->getDevicesList());

		foreach ($response->getData()->getDevicesList() as $subDevice) {
			self::assertTrue($subDevice->getState() !== []);
		}

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/get_sub_devices.json',
			Utils\Json::encode($response->toJson()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testSetSubDeviceState(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(
				Utils\FileSystem::read(__DIR__ . '/../../../fixtures/API/responses/set_sub_device_state.json'),
			);

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->with(
				self::callback(static function (Http\Message\RequestInterface $request): bool {
					self::assertSame('PUT', $request->getMethod());
					self::assertSame(
						'http://127.0.0.1:8081/open-api/v1/rest/devices/a480062416',
						strval($request->getUri()),
					);
					self::assertSame(
						[
							'Host' => [
								'127.0.0.1:8081',
							],
							'Content-Type' => [
								'application/json',
							],
							'Authorization' => [
								'Bearer abcdefghijklmnopqrstuvwxyz',
							],
						],
						$request->getHeaders(),
					);

					$actual = Utils\Json::decode($request->getBody()->getContents(), forceArrays: true);
					self::assertTrue(is_array($actual));

					$request->getBody()->rewind();

					Tests\Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/API/request/set_sub_device_state.json',
						$request->getBody()->getContents(),
						static function (string $expectation) use ($actual): string {
							if (
								isset($actual['event'])
								&& is_array($actual['event'])
								&& isset($actual['event']['header'])
								&& is_array($actual['event']['header'])
								&& isset($actual['event']['header']['message_id'])
							) {
								$expectation = str_replace(
									'__MESSAGE_ID__',
									strval($actual['event']['header']['message_id']),
									$expectation,
								);
							}

							return $expectation;
						},
					);

					return true;
				}),
			)
			->willReturn($response);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getId());

		$response = $lanApi->setSubDeviceState(
			'a480062416',
			[
				'power' => [
					'powerState' => 'on',
				],
			],
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/responses/set_sub_device_state.json',
			Utils\Json::encode($response->toJson()),
		);
	}

}
