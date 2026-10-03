<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
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
final class DevicePropertyChildrenV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('devicePropertyChildrenRead')]
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
	public static function devicePropertyChildrenRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.property.children.index.json',
			],
			'readConnectorDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.property.children.index.connector.json',
			],

			// Invalid responses
			////////////////////
			'readWrongDevice' => [
				// Property of the first device, requested through the second device
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/devices/bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readWrongConnector' => [
				// Device of the generic connector, addressed through the dummy connector
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/7a3dd94c-7294-46fd-8c61-1b375c313d4d/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
