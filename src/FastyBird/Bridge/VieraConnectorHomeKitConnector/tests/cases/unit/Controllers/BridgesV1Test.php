<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React\Http\Message\ServerRequest;
use RuntimeException;
use function file_get_contents;
use function is_array;
use function str_replace;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class BridgesV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('bridgesRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function bridgesRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.index.paging.json',
			],
			'readOne' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.read.json',
			],
			'readRelationshipsProperties' => [
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/1d0f40bf-e023-4e62-8bec-7a5d81e40e84/relationships/properties',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.properties.json',
			],
			'readRelationshipsChannels' => [
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/1d0f40bf-e023-4e62-8bec-7a5d81e40e84/relationships/channels',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.channels.json',
			],
			'readRelationshipsChildren' => [
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/1d0f40bf-e023-4e62-8bec-7a5d81e40e84/relationships/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.children.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/69786d15-fd0c-4d9f-9378-33287c2009af',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/1d0f40bf-e023-4e62-8bec-7a5d81e40e84/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009af/relationships/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllMissingToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('bridgesCreate')]
	public function testCreate(
		string $url,
		string|null $token,
		string $body,
		int $statusCode,
		string $fixture,
	): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());

		$responseBody = (string) $response->getBody();

		$actual = Utils\Json::decode($responseBody, forceArrays: true);
		self::assertTrue(is_array($actual));

		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
			static function (string $expectation) use ($actual): string {
				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['properties'])
					&& is_array($actual['data']['relationships']['properties'])
					&& isset($actual['data']['relationships']['properties']['data'])
					&& is_array($actual['data']['relationships']['properties']['data'])
				) {
					$variable = $dynamic = 1;

					foreach ($actual['data']['relationships']['properties']['data'] as $data) {
						if (!isset($data['id']) || !isset($data['type'])) {
							continue;
						}

						if ($data['type'] === 'com.fastybird.devices-module/property/device/variable') {
							$expectation = str_replace(
								'__PROPERTY_VARIABLE_' . $variable . '_PLACEHOLDER__',
								strval($data['id']),
								$expectation,
							);

							++$variable;
						} elseif ($data['type'] === 'com.fastybird.devices-module/property/device/dynamic') {
							$expectation = str_replace(
								'__PROPERTY_DYNAMIC_' . $dynamic . '_PLACEHOLDER__',
								strval($data['id']),
								$expectation,
							);

							++$dynamic;
						}
					}
				}

				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['channels'])
					&& is_array($actual['data']['relationships']['channels'])
					&& isset($actual['data']['relationships']['channels']['data'])
					&& is_array($actual['data']['relationships']['channels']['data'])
				) {
					$channel = 1;

					foreach ($actual['data']['relationships']['channels']['data'] as $data) {
						if (!isset($data['id']) || !isset($data['type'])) {
							continue;
						}

						$expectation = str_replace(
							'__CHANNEL_' . $channel . '_PLACEHOLDER__',
							strval($data['id']),
							$expectation,
						);

						$expectation = str_replace(
							'__CHANNEL_' . $channel . '_TYPE_PLACEHOLDER__',
							strval($data['type']),
							$expectation,
						);

						++$channel;
					}
				}

				return $expectation;
			},
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function bridgesCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.missing.required.json',
			],
			'notUnique' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.notUnique.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.notUnique.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.invalid.type.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'missingToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('bridgesUpdate')]
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function bridgesUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.update.json',
			],

			// Invalid responses
			////////////////////
			'invalidType' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.invalid.type.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.invalid.id.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('bridgesDelete')]
	public function testDelete(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function bridgesDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.delete.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/69786d15-fd0c-4d9f-9378-33287c2009af',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/1d0f40bf-e023-4e62-8bec-7a5d81e40e84',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
