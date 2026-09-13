<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\API;

use Error;
use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
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
final class Gen2HttpApiTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 * @throws Utils\JsonException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDeviceInformation(): void
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
						'http://10.10.0.100/rpc/Shelly.GetDeviceInfo',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen2Http/response/get_device_information.json',
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

		$gen2ApiFactory = $this->getContainer()->getByType(API\Gen2HttpApiFactory::class);

		$gen2Api = $gen2ApiFactory->create();

		$deviceInformation = $gen2Api->getDeviceInformation('10.10.0.100', false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen2Http/result_get_device_information.json',
			Utils\Json::encode($deviceInformation->toArray()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 * @throws Utils\JsonException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetDeviceConfiguration(): void
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
						'http://10.10.0.100/rpc/Shelly.GetConfig',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen2Http/response/get_device_configuration.json',
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

		$gen2ApiFactory = $this->getContainer()->getByType(API\Gen2HttpApiFactory::class);

		$gen2Api = $gen2ApiFactory->create();

		$deviceConfiguration = $gen2Api->getDeviceConfiguration('10.10.0.100', null, null, false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen2Http/result_get_device_configuration.json',
			Utils\Json::encode($deviceConfiguration->toArray()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 * @throws Utils\JsonException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
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

					self::assertSame(
						'http://10.10.0.100/rpc/Shelly.GetStatus',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen2Http/response/get_device_state.json',
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

		$gen2ApiFactory = $this->getContainer()->getByType(API\Gen2HttpApiFactory::class);

		$gen2Api = $gen2ApiFactory->create();

		$deviceState = $gen2Api->getDeviceState('10.10.0.100', null, null, false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen2Http/result_get_device_state.json',
			Utils\Json::encode($deviceState->toArray()),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
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

					self::assertSame(
						'http://10.10.0.100/rpc',
						strval($request->getUri()),
					);

					self::assertSame(
						RequestMethodInterface::METHOD_POST,
						$request->getMethod(),
					);

					$actual = Utils\Json::decode($request->getBody()->getContents(), forceArrays: true);
					self::assertTrue(is_array($actual));

					$request->getBody()->rewind();

					Tests\Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/API/Gen2Http/request/set_device_state.json',
						$request->getBody()->getContents(),
						static function (string $expectation) use ($actual): string {
							if (isset($actual['id'])) {
								$expectation = str_replace(
									'__MESSAGE_ID__',
									strval($actual['id']),
									$expectation,
								);
							}

							return $expectation;
						},
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen2Http/response/set_device_state.json',
							),
						);

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);
					$response
						->method('getStatusCode')
						->willReturn(StatusCodeInterface::STATUS_OK);

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

		$gen2ApiFactory = $this->getContainer()->getByType(API\Gen2HttpApiFactory::class);

		$gen2Api = $gen2ApiFactory->create();

		$deviceState = $gen2Api->setDeviceState('10.10.0.100', null, null, 'switch_0_output', false, false);

		self::assertTrue($deviceState);
	}

}
