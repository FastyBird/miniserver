<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Tests;
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

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ChannelPropertyChildrenV1Test extends Tests\Cases\Unit\DbTestCase
{

	public function setUp(): void
	{
		// The shared dummy data has no mapped channel property, so no channel property has children
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/channel.property.mapped.sql');

		parent::setUp();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('channelPropertyChildrenRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

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
		Tests\Support\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function channelPropertyChildrenRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readDeviceChannel' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/channel.property.children.index.json',
			],
			'readChannel' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/channel.property.children.index.channel.json',
			],
			'readWithoutChildren' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/24c436f4-a2e4-4d2b-b910-1a3ff785b784/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/channel.property.children.index.empty.json',
			],

			// Invalid responses
			////////////////////
			'readWrongChannel' => [
				// Property of channel two, requested through channel one of the same device
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/channels/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readUnknownDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readChannelOfOtherDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readUnknownChannel' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/channels/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/channels/6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
