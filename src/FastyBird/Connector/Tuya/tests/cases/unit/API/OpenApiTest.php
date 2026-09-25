<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\API;

use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Http;
use RuntimeException;
use function in_array;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class OpenApiTest extends Tests\Cases\Unit\DbTestCase
{

	private const ACCESS_ID = 'MftAcceHZL11BpOR';

	private const ACCESS_SECRET = 'dBCQZohQNR2U4rW9';

	private const UID = 'Bjhq01pE7q4ijNMN';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDevices(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/users/userid123/devices',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/users/userid123/devices') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_devices.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDevices = $openApi->getUserDevices('userid123', false);

		self::assertCount(4, $userDevices->getResult());
		self::assertSame([
			'result' => [
				[
					'id' => 'bf3e9d85a52b163f940wgx',
					'name' => 'Wall socket - outdoor',
					'uid' => self::UID,
					'local_key' => 'fea74f634dc369c1',
					'category' => 'cz',
					'product_id' => 'pnzfdr9y',
					'product_name' => 'Outdoor Socket Adapter',
					'sub' => true,
					'uuid' => '9035eafffeb8f501',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [
						0 => [
							'code' => 'switch_1',
							'value' => false,
							'type' => null,
						],
						1 => [
							'code' => 'countdown_1',
							'value' => 0.0,
							'type' => null,
						],
					],
					'active_time' => 1_669_988_230,
					'create_time' => 1_669_988_230,
					'update_time' => 1_692_389_367,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
					'ip' => null,
					'time_zone' => '+01:00',
				],
				[
					'id' => 'bfa1a65b1d7f75a9aenvkc',
					'name' => 'NEO Gateway',
					'uid' => self::UID,
					'local_key' => 'qlD53YUXfxboDFpb',
					'category' => 'wg2',
					'product_id' => 'be9fookuobd9w8z3',
					'product_name' => 'NEO Gateway',
					'sub' => false,
					'uuid' => '52f41514cef56dbb',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [],
					'active_time' => 1_669_643_113,
					'create_time' => 1_669_392_668,
					'update_time' => 1_692_385_165,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1503986080106Gppjy/1d241ba5f7e7b5139ee6bbf2b0eeb11b.png',
					'ip' => '85.160.10.238',
					'time_zone' => '+01:00',
				],
				[
					'id' => 'bfa51eb7b64c2f5eedradw',
					'name' => 'Living room environment',
					'uid' => self::UID,
					'local_key' => 'eL255qwTWzj4tJZr',
					'category' => 'ldcg',
					'product_id' => 'ftdkanlj',
					'product_name' => 'Luminance sensor',
					'sub' => true,
					'uuid' => '5c0272fffe037960',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [
						0 => [
							'code' => 'bright_value',
							'value' => 0.0,
							'type' => null,
						],
						1 => [
							'code' => 'battery_percentage',
							'value' => 93.0,
							'type' => null,
						],
						2 => [
							'code' => 'temp_current',
							'value' => 281.0,
							'type' => null,
						],
						3 => [
							'code' => 'humidity_value',
							'value' => 518.0,
							'type' => null,
						],
						4 => [
							'code' => 'bright_sensitivity',
							'value' => 10.0,
							'type' => null,
						],
					],
					'active_time' => 1_669_643_114,
					'create_time' => 1_669_393_353,
					'update_time' => 1_692_235_573,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay15327721968035jwx9/9ef66b23e59bd8a8c4da13536be92eb6.png',
					'ip' => null,
					'time_zone' => '+01:00',
				],
				[
					'id' => '402675772462ab280dae',
					'name' => 'WiFi Smart Timer',
					'uid' => self::UID,
					'local_key' => 'YyGzzRui2Xej4D04',
					'category' => 'kg',
					'product_id' => 'SJet14RibkVEZDOB',
					'product_name' => 'WiFi Smart Timer',
					'sub' => false,
					'uuid' => '402675772462ab280dae',
					'owner_id' => '44154302',
					'online' => false,
					'status' => [
						0 => [
							'code' => 'switch',
							'value' => true,
							'type' => null,
						],
						1 => [
							'code' => 'countdown_1',
							'value' => 0.0,
							'type' => null,
						],
					],
					'active_time' => 1_640_898_671,
					'create_time' => 1_594_720_605,
					'update_time' => 1_671_125_276,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1522655691209YPydg/15601526771def4a0a3e0.jpg',
					'ip' => '80.78.136.56',
					'time_zone' => '+01:00',
				],
			],
		], $userDevices->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDevicesFactoryInfos(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/devices/factory-infos?device_ids=bf3e9d85a52b163f940wgx%2Cbfa1a65b1d7f75a9aenvkc',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/devices/factory-infos?device_ids=bf3e9d85a52b163f940wgx%2Cbfa1a65b1d7f75a9aenvkc') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_devices_factory_infos.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDevicesInfos = $openApi->getUserDevicesFactoryInfos(
			['bf3e9d85a52b163f940wgx', 'bfa1a65b1d7f75a9aenvkc'],
			false,
		);

		self::assertCount(2, $userDevicesInfos->getResult());
		self::assertSame([
			'result' => [
				[
					'id' => 'bf3e9d85a52b163f940wgx',
					'uuid' => '9035eafffeb8f501',
					'sn' => '100048644013EC',
					'mac' => '90:35:ea:ff:fe:b8:f5:01',
				],
				[
					'id' => 'bfa1a65b1d7f75a9aenvkc',
					'uuid' => '52f41514cef56dbb',
					'sn' => 'NEO2022101800100137',
					'mac' => 'fc:67:1f:7d:b8:c4',
				],
			],
		], $userDevicesInfos->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDeviceDetail(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_device_detail.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDeviceDetail = $openApi->getUserDeviceDetail('bf3e9d85a52b163f940wgx', false);

		self::assertSame([
			'result' => [
				'id' => 'bf3e9d85a52b163f940wgx',
				'name' => 'Wall socket - outdoor',
				'uid' => self::UID,
				'local_key' => 'fea74f634dc369c1',
				'category' => 'cz',
				'product_id' => 'pnzfdr9y',
				'product_name' => 'Outdoor Socket Adapter',
				'sub' => true,
				'uuid' => '9035eafffeb8f501',
				'owner_id' => '44154302',
				'online' => true,
				'status' => [
					0 => [
						'code' => 'switch_1',
						'value' => false,
						'type' => null,
					],
					1 => [
						'code' => 'countdown_1',
						'value' => 0.0,
						'type' => null,
					],
				],
				'active_time' => 1_669_988_230,
				'create_time' => 1_669_988_230,
				'update_time' => 1_692_402_101,
				'biz_type' => 0,
				'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
				'ip' => null,
				'time_zone' => '+01:00',
			],
		], $userDeviceDetail->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDeviceSpecifications(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx/specifications',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx/specifications') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_device_specifications.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDeviceSpecifications = $openApi->getUserDeviceSpecifications('bf3e9d85a52b163f940wgx', false);

		self::assertSame([
			'result' => [
				'category' => 'cz',
				'functions' => [
					[
						'code' => 'switch_1',
						'type' => 'Boolean',
						'values' => '{}',
					],
					[
						'code' => 'countdown_1',
						'type' => 'Integer',
						'values' => '{"unit":"s","min":0,"max":43200,"scale":0,"step":1}',
					],
				],
				'status' => [
					[
						'code' => 'switch_1',
						'type' => 'Boolean',
						'values' => '{}',
					],
					[
						'code' => 'countdown_1',
						'type' => 'Integer',
						'values' => '{"unit":"s","min":0,"max":43200,"scale":0,"step":1}',
					],
				],
			],
		], $userDeviceSpecifications->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDeviceState(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx/status',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/devices/bf3e9d85a52b163f940wgx/status') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_device_state.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDeviceSpecifications = $openApi->getUserDeviceState('bf3e9d85a52b163f940wgx', false);

		self::assertSame([
			'result' => [
				[
					'code' => 'switch_1',
					'value' => false,
					'type' => null,
				],
				[
					'code' => 'countdown_1',
					'value' => 0.0,
					'type' => null,
				],
			],
		], $userDeviceSpecifications->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDeviceChildren(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/devices/bfa1a65b1d7f75a9aenvkc/sub-devices',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/devices/bfa1a65b1d7f75a9aenvkc/sub-devices') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_device_children.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$userDeviceChildren = $openApi->getUserDeviceChildren('bfa1a65b1d7f75a9aenvkc', false);

		self::assertSame([
			'result' => [
				[
					'id' => 'bf3e9d85a52b163f940wgx',
					'node_id' => '9035eafffeb8f501',
					'name' => 'Wall socket - outdoor',
					'product_id' => 'pnzfdr9y',
					'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
					'online' => true,
				],
				[
					'id' => 'bfa51eb7b64c2f5eedradw',
					'node_id' => '5c0272fffe037960',
					'name' => 'Living room environment',
					'product_id' => 'ftdkanlj',
					'icon' => 'smart/icon/ay15327721968035jwx9/9ef66b23e59bd8a8c4da13536be92eb6.png',
					'online' => true,
				],
			],
		], $userDeviceChildren->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDevices(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.3/iot-03/devices?source_id=' . self::UID . '&source_type=tuyaUser',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.3/iot-03/devices?source_id=' . self::UID . '&source_type=tuyaUser') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_devices.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$devices = $openApi->getDevices([
			'source_id' => self::UID,
			'source_type' => 'tuyaUser',
		], false);

		self::assertCount(4, $devices->getResult()->getList());
		self::assertSame([
			'result' => [
				'has_more' => false,
				'last_row_key' => '629B04066A0B5A330FFFDF1801238A8A638BBE04B82CA24429FA7478C93D3F74',
				'total' => 18,
				'list' => [
					[
						'id' => 'bf3e9d85a52b163f940wgx',
						'gateway_id' => 'bfa1a65b1d7f75a9aenvkc',
						'node_id' => '9035eafffeb8f501',
						'uuid' => '9035eafffeb8f501',
						'category' => 'cz',
						'category_name' => 'Socket',
						'name' => 'Wall socket - outdoor',
						'product_id' => 'pnzfdr9y',
						'product_name' => 'Outdoor Socket Adapter',
						'local_key' => 'fea74f634dc369c1',
						'sub' => true,
						'asset_id' => null,
						'owner_id' => '44154302',
						'ip' => null,
						'lon' => '16.8096',
						'lat' => '49.1310',
						'model' => null,
						'time_zone' => '+01:00',
						'active_time' => 1_669_988_230,
						'create_time' => 1_669_988_230,
						'update_time' => 1_692_402_101,
						'online' => true,
						'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
					],
					[
						'id' => 'bfa1a65b1d7f75a9aenvkc',
						'gateway_id' => null,
						'node_id' => null,
						'uuid' => '52f41514cef56dbb',
						'category' => 'wg2',
						'category_name' => 'Gateway',
						'name' => 'NEO Gateway',
						'product_id' => 'be9fookuobd9w8z3',
						'product_name' => 'NEO Gateway',
						'local_key' => 'qlD53YUXfxboDFpb',
						'sub' => false,
						'asset_id' => null,
						'owner_id' => '44154302',
						'ip' => '85.160.10.238',
						'lon' => '16.8096',
						'lat' => '49.1310',
						'model' => 'NAS-ZW05B0',
						'time_zone' => '+01:00',
						'active_time' => 1_669_643_113,
						'create_time' => 1_669_392_668,
						'update_time' => 1_692_385_165,
						'online' => true,
						'icon' => 'smart/icon/ay1503986080106Gppjy/1d241ba5f7e7b5139ee6bbf2b0eeb11b.png',
					],
					[
						'id' => 'bfa51eb7b64c2f5eedradw',
						'gateway_id' => 'bfa1a65b1d7f75a9aenvkc',
						'node_id' => '5c0272fffe037960',
						'uuid' => '5c0272fffe037960',
						'category' => 'ldcg',
						'category_name' => 'Luminance Sensor',
						'name' => 'Living room environment',
						'product_id' => 'ftdkanlj',
						'product_name' => 'Luminance sensor',
						'local_key' => 'eL255qwTWzj4tJZr',
						'sub' => true,
						'asset_id' => null,
						'owner_id' => '44154302',
						'ip' => null,
						'lon' => '16.8096',
						'lat' => '49.1310',
						'model' => 'ZSS-X-THL',
						'time_zone' => '+01:00',
						'active_time' => 1_669_643_114,
						'create_time' => 1_669_393_353,
						'update_time' => 1_692_235_573,
						'online' => true,
						'icon' => 'smart/icon/ay15327721968035jwx9/9ef66b23e59bd8a8c4da13536be92eb6.png',
					],
					[
						'id' => '402675772462ab280dae',
						'gateway_id' => null,
						'node_id' => null,
						'uuid' => '402675772462ab280dae',
						'category' => 'kg',
						'category_name' => 'Switch',
						'name' => 'WiFi Smart Timer',
						'product_id' => 'SJet14RibkVEZDOB',
						'product_name' => 'WiFi Smart Timer',
						'local_key' => 'YyGzzRui2Xej4D04',
						'sub' => false,
						'asset_id' => null,
						'owner_id' => '44154302',
						'ip' => '80.78.136.56',
						'lon' => '16.8096',
						'lat' => '49.1310',
						'model' => 'ATMS1601',
						'time_zone' => '+01:00',
						'active_time' => 1_640_898_671,
						'create_time' => 1_594_720_605,
						'update_time' => 1_671_125_276,
						'online' => false,
						'icon' => 'smart/icon/ay1522655691209YPydg/15601526771def4a0a3e0.jpg',
					],

				],
			],
		], $devices->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDevicesFactoryInfos(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/iot-03/devices/factory-infos?device_ids=bf3e9d85a52b163f940wgx%2Cbfa1a65b1d7f75a9aenvkc',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/iot-03/devices/factory-infos?device_ids=bf3e9d85a52b163f940wgx%2Cbfa1a65b1d7f75a9aenvkc') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_devices_factory_infos.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$devicesInfos = $openApi->getDevicesFactoryInfos(
			['bf3e9d85a52b163f940wgx', 'bfa1a65b1d7f75a9aenvkc'],
			false,
		);

		self::assertCount(2, $devicesInfos->getResult());
		self::assertSame([
			'result' => [
				[
					'id' => 'bf3e9d85a52b163f940wgx',
					'uuid' => '9035eafffeb8f501',
					'sn' => '100048644013EC',
					'mac' => '9035eafffeb8f501',
				],
				[
					'id' => 'bfa1a65b1d7f75a9aenvkc',
					'uuid' => '52f41514cef56dbb',
					'sn' => 'NEO2022101800100137',
					'mac' => 'fc671f7db8c4',
				],
			],
		], $devicesInfos->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDeviceDetail(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.1/iot-03/devices/bf3e9d85a52b163f940wgx',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.1/iot-03/devices/bf3e9d85a52b163f940wgx') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_device_detail.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$device = $openApi->getDeviceDetail('bf3e9d85a52b163f940wgx', false);

		self::assertSame([
			'result' => [
				'id' => 'bf3e9d85a52b163f940wgx',
				'gateway_id' => 'bfa1a65b1d7f75a9aenvkc',
				'node_id' => '9035eafffeb8f501',
				'uuid' => '9035eafffeb8f501',
				'category' => 'cz',
				'category_name' => 'Socket',
				'name' => 'Wall socket - outdoor',
				'product_id' => 'pnzfdr9y',
				'product_name' => 'Outdoor Socket Adapter',
				'local_key' => 'fea74f634dc369c1',
				'sub' => true,
				'asset_id' => null,
				'owner_id' => '44154302',
				'ip' => null,
				'lon' => '16.8096',
				'lat' => '49.1310',
				'model' => null,
				'time_zone' => '+01:00',
				'active_time' => 1_669_988_230,
				'create_time' => 1_669_988_230,
				'update_time' => 1_692_631_717,
				'online' => true,
				'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
			],
		], $device->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDeviceSpecification(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.2/iot-03/devices/bf3e9d85a52b163f940wgx/specification',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.2/iot-03/devices/bf3e9d85a52b163f940wgx/specification') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_device_specification.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$deviceSpecification = $openApi->getDeviceSpecification(
			'bf3e9d85a52b163f940wgx',
			false,
		);

		self::assertSame([
			'result' => [
				'category' => 'cz',
				'functions' => [
					[
						'code' => 'switch_1',
						'type' => 'Boolean',
						'values' => '{}',
					],
					[
						'code' => 'countdown_1',
						'type' => 'Integer',
						'values' => '{"unit":"s","min":0,"max":43200,"scale":0,"step":1}',
					],
				],
				'status' => [
					[
						'code' => 'switch_1',
						'type' => 'Boolean',
						'values' => '{}',
					],
					[
						'code' => 'countdown_1',
						'type' => 'Integer',
						'values' => '{"unit":"s","min":0,"max":43200,"scale":0,"step":1}',
					],
				],

			],
		], $deviceSpecification->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDeviceState(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/iot-03/devices/bf3e9d85a52b163f940wgx/status',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/iot-03/devices/bf3e9d85a52b163f940wgx/status') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_device_state.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$deviceState = $openApi->getDeviceState('bf3e9d85a52b163f940wgx', false);

		self::assertSame([
			'result' => [
				[
					'code' => 'switch_1',
					'value' => false,
					'type' => null,
				],
				[
					'code' => 'countdown_1',
					'value' => 0.0,
					'type' => null,
				],
			],
		], $deviceState->toArray());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testSetDeviceState(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertTrue(in_array(
						strval($request->getUri()),
						[
							'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
							'https://openapi.tuyaeu.com/v1.0/iot-03/devices/bf3e9d85a52b163f940wgx/commands',
						],
						true,
					));

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/iot-03/devices/bf3e9d85a52b163f940wgx/commands') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/set_device_state.json',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$result = $openApi->setDeviceState('bf3e9d85a52b163f940wgx', 'switch_1', false, false);

		self::assertTrue($result);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testConnect(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					self::assertSame(
						'https://openapi.tuyaeu.com/v1.0/token?grant_type=1',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/response/connect.json',
							),
						);

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			self::ACCESS_ID,
			self::ACCESS_SECRET,
			Types\OpenApiEndpoint::EUROPE,
		);

		$openApi->connect(false);
	}

}
