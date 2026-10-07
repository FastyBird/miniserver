<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DevicePropertyStateV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const PROPERTY_ID = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('devicePropertyStateRead')]
	public function testRead(
		string $url,
		string|null $token,
		int $lookups,
		bool $stored,
		int $statusCode,
		string $fixture,
	): void
	{
		// The storage driver behind the module's state repository; production registers it through a bridge
		$stateRepository = $this->createMock(Models\States\Devices\IRepository::class);
		$stateRepository
			->expects(self::exactly($lookups))
			->method('find')
			->with(self::callback(
				static fn (Uuid\UuidInterface $id): bool => $id->toString() === self::PROPERTY_ID,
			))
			->willReturnCallback(
				static fn (Uuid\UuidInterface $id): Tests\Fixtures\Dummy\DevicePropertyState|null => $stored
					? new Tests\Fixtures\Dummy\DevicePropertyState(
						$id,
						'3600',
						null,
						false,
						true,
					)
					: null,
			);

		$this->mockContainerService(
			Models\States\Devices\Repository::class,
			new Models\States\Devices\Repository(
				$this->getContainer()->getByType(Caching\Container::class),
				$stateRepository,
			),
		);

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
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|bool|null>>
	 */
	public static function devicePropertyStateRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				true,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.property.state.read.json',
			],
			'readConnectorDevice' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				true,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.property.state.read.connector.json',
			],

			// Invalid responses
			////////////////////
			'readWrongConnector' => [
				// Device of the generic connector, addressed through the dummy connector
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/7a3dd94c-7294-46fd-8c61-1b375c313d4d/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				0,
				true,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readNoState' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				false,
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.property.state.missing.json',
			],
			'readVariableProperty' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/3ff0029f-7fe3-405e-a3ef-edaad08e2ffa/state',
				'Bearer ' . self::VALID_TOKEN,
				0,
				true,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . \FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/' . self::PROPERTY_ID . '/state',
				null,
				0,
				true,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
