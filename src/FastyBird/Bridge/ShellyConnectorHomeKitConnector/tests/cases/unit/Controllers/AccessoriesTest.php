<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Cases\Unit\Controllers;

use Doctrine\DBAL;
use Error;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Middleware as HomeKitMiddleware;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Connector\HomeKit\Servers as HomeKitServers;
use FastyBird\Core\Application\EventLoop as ApplicationEventLoop;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\SlimRouter\Http as SlimRouterHttp;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use RuntimeException;
use z4kn4fein\SemVer;
use function call_user_func;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class AccessoriesTest extends Tests\Cases\Unit\DbTestCase
{

	private HomeKitServers\Http|null $httpServer = null;

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Mapping
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws SemVer\SemverException
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function setUp(): void
	{
		parent::setUp();

		$eventLoop = $this->createMock(ApplicationEventLoop\Wrapper::class);

		$this->mockContainerService(ApplicationEventLoop\Wrapper::class, $eventLoop);

		$repository = $this->getContainer()->getByType(DevicesModels\Configuration\Connectors\Repository::class);

		$findConnectorQuery = new HomeKitQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString('451ab010-f500-4eff-8289-9ed09e56a887'));

		$connector = $repository->findOneBy(
			$findConnectorQuery,
			HomeKitDocuments\Connectors\Connector::class,
		);
		self::assertInstanceOf(HomeKitDocuments\Connectors\Connector::class, $connector);

		$accessoryLoader = $this->getContainer()->getByType(HomeKitProtocol\Loader::class);

		$accessoryLoader->load($connector);

		$httpServerFactory = $this->getContainer()->getByType(HomeKitServers\HttpFactory::class);

		$this->httpServer = $httpServerFactory->create($connector);
		$this->httpServer->initialize();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->httpServer?->disconnect();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider accessoriesRead
	 */
	public function testRead(string $url, int $statusCode, string $fixture): void
	{
		$middleware = $this->getContainer()->getByType(HomeKitMiddleware\Router::class);

		$headers = [];

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers,
			'',
			'1.1',
			[
				'REMOTE_ADDR' => '127.0.0.1',
			],
		);

		$request = $request->withAttribute(
			HomeKitServers\Http::REQUEST_ATTRIBUTE_CONNECTOR,
			'451ab010-f500-4eff-8289-9ed09e56a887',
		);

		$response = call_user_func($middleware, $request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function accessoriesRead(): array
	{
		return [
			'readAll' => [
				'/accessories',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accessories.json',
			],
		];
	}

}
