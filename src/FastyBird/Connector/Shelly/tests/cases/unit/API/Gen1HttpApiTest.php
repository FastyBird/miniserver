<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\API;

use Error;
use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Http;
use RuntimeException;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class Gen1HttpApiTest extends Tests\Cases\Unit\DbTestCase
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
						'http://10.10.0.100/shelly',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen1Http/response/get_device_information.json',
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

		$gen1ApiFactory = $this->getContainer()->getByType(API\Gen1HttpApiFactory::class);

		$gen1Api = $gen1ApiFactory->create();

		$deviceInformation = $gen1Api->getDeviceInformation('10.10.0.100', false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen1Http/result_get_device_information.json',
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
	public function testGetDeviceDescription(): void
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
						'http://10.10.0.100/cit/d',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen1Http/response/get_device_description.json',
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

		$gen1ApiFactory = $this->getContainer()->getByType(API\Gen1HttpApiFactory::class);

		$gen1Api = $gen1ApiFactory->create();

		$deviceDescription = $gen1Api->getDeviceDescription('10.10.0.100', null, null, false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen1Http/result_get_device_description.json',
			Utils\Json::encode($deviceDescription->toArray()),
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
						'http://10.10.0.100/status',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen1Http/response/get_device_state.json',
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

		$gen1ApiFactory = $this->getContainer()->getByType(API\Gen1HttpApiFactory::class);

		$gen1Api = $gen1ApiFactory->create();

		$deviceState = $gen1Api->getDeviceState('10.10.0.100', null, null, false);

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/API/Gen1Http/result_get_device_state.json',
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
						'http://10.10.0.100/light/0?turn=on',
						strval($request->getUri()),
					);

					$responseBody
						->method('getContents')
						->willReturn(
							Utils\FileSystem::read(
								__DIR__ . '/../../../fixtures/API/Gen1Http/response/set_device_state.json',
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

		$gen1ApiFactory = $this->getContainer()->getByType(API\Gen1HttpApiFactory::class);

		$gen1Api = $gen1ApiFactory->create();

		$deviceState = $gen1Api->setDeviceState('10.10.0.100', null, null, '1_light_0', '1101_S_output', 'on', false);

		self::assertTrue($deviceState);
	}

}
