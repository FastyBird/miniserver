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
final class ConnectorPropertyStateV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const PROPERTY_ID = '1b17bcaa-a19e-45f0-98b5-6a3b2c5d0ab9';

	public function setUp(): void
	{
		// The shared dummy data has only variable connector properties, and only a dynamic one has a state
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/connector.property.dynamic.sql');

		parent::setUp();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('connectorPropertyStateRead')]
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
		$stateRepository = $this->createMock(Models\States\Connectors\IRepository::class);
		$stateRepository
			->expects(self::exactly($lookups))
			->method('find')
			->with(self::callback(
				static fn (Uuid\UuidInterface $id): bool => $id->toString() === self::PROPERTY_ID,
			))
			->willReturnCallback(
				static fn (Uuid\UuidInterface $id): Tests\Fixtures\Dummy\ConnectorPropertyState|null => $stored
					? new Tests\Fixtures\Dummy\ConnectorPropertyState(
						$id,
						'1',
						null,
						false,
						true,
					)
					: null,
			);

		$this->mockContainerService(
			Models\States\Connectors\Repository::class,
			new Models\States\Connectors\Repository(
				$this->getContainer()->getByType(Caching\Container::class),
				$stateRepository,
			),
		);

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
	 * @return array<string, array<string|int|bool|null>>
	 */
	public static function connectorPropertyStateRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readConnector' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				true,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connector.property.state.read.json',
			],

			// Invalid responses
			////////////////////
			'readNoState' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/properties/' . self::PROPERTY_ID . '/state',
				'Bearer ' . self::VALID_TOKEN,
				1,
				false,
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/connector.property.state.missing.json',
			],
			'readVariableProperty' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/properties/5a8b01f2-621c-4c41-bc83-c089d72b2366/state',
				'Bearer ' . self::VALID_TOKEN,
				0,
				true,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/properties/' . self::PROPERTY_ID . '/state',
				null,
				0,
				true,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
