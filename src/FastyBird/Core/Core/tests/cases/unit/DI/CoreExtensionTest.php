<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Commands as HttpServerCommands;
use FastyBird\Core\Commands as WsServerCommands;
use FastyBird\Core\Configuration;
use FastyBird\Core\Controllers as WebSocketsControllers;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Encoding as JsonApiEncoding;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers as ToolsHelpers;
use FastyBird\Core\Http as WebServerHttp;
use FastyBird\Core\Messaging as ExchangeMessaging;
use FastyBird\Core\Middleware as WebServerMiddleware;
use FastyBird\Core\Phone\Services as PhoneServices;
use FastyBird\Core\Phone\Subscribers as PhoneSubscribers;
use FastyBird\Core\Schemas as ToolsSchemas;
use FastyBird\Core\Server as HttpServerServer;
use FastyBird\Core\Services as SimpleAuthServices;
use FastyBird\Core\Subscribers as DoctrineTimestampableSubscribers;
use FastyBird\Core\Subscribers as HttpServerSubscribers;
use FastyBird\Core\Subscribers as WsServerSubscribers;
use FastyBird\Core\Tests;
use Monolog;
use Nette;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use React\EventLoop;
use Symfony\Bridge\Monolog as SymfonyMonolog;

/**
 * Replaces ApplicationExtensionTest, ExchangeExtensionTest, ToolsExtensionTest,
 * WebServerExtensionTest and WsServerExtensionTest -- the five DI-extension tests that existed
 * across the fourteen extensions CoreExtension consolidates (Tasks 3, 9, 13, 16 and 17 deleted
 * them one package at a time as each package's content moved into Core/Core; the other nine
 * extensions -- SimpleAuth, DateTimeFactory, DoctrineCrud, DoctrineTimestampable, JsonApi,
 * Phone, DoctrinePhone, WebSockets and WebSocketsWAMP -- never had a dedicated DI test at all).
 * This class asserts the union of what those five tested, plus representative coverage of the
 * domains that were never tested before, plus both halves of each of the two service-key
 * collisions this task's own investigation found and resolved.
 */
final class CoreExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		$container = $this->createContainer();

		/**
		 * APPLICATION -- from ApplicationExtensionTest
		 */

		self::assertNotNull($container->getByType(Monolog\Handler\RotatingFileHandler::class, false));
		self::assertNull($container->getByType(SymfonyMonolog\Handler\ConsoleHandler::class, false));
		self::assertNotNull($container->getByType(ApplicationDocuments\DocumentFactory::class, false));
		self::assertInstanceOf(
			ApplicationDocuments\DocumentFactory::class,
			$container->getService('document.factory'),
		);

		/**
		 * EXCHANGE -- from ExchangeExtensionTest
		 */

		self::assertNotNull($container->getByType(ExchangeDocuments\RoutingDocumentFactory::class, false));
		self::assertNotNull($container->getByType(ExchangeMessaging\Exchange\Publisher\Container::class, false));
		self::assertNotNull($container->getByType(ExchangeMessaging\Exchange\Publisher\Async\Container::class, false));
		self::assertNotNull($container->getByType(ExchangeMessaging\Exchange\Consumers\Container::class, false));

		/**
		 * TOOLS -- from ToolsExtensionTest
		 */

		self::assertNotNull($container->getByType(ToolsHelpers\Tools\Database::class, false));
		self::assertNotNull($container->getByType(ToolsSchemas\Tools\Validator::class, false));

		/**
		 * HTTP SERVER (formerly Plugin/WebServer) -- from WebServerExtensionTest
		 */

		self::assertNotNull($container->getByType(HttpServerServer\HttpServer\Application::class, false));
		self::assertNotNull($container->getByType(HttpServerCommands\HttpServer::class, false));
		self::assertNotNull($container->getByType(WebServerHttp\ServerResponseFactory::class, false));
		self::assertNotNull($container->getByType(EventLoop\LoopInterface::class, false));
		self::assertNotNull($container->getByType(WebServerMiddleware\WebServer\Cors::class, false));
		self::assertNotNull($container->getByType(WebServerMiddleware\WebServer\StaticFiles::class, false));
		self::assertNotNull($container->getByType(WebServerMiddleware\WebServer\Router::class, false));
		self::assertNotNull($container->getByType(HttpServerServer\HttpServer\Factory::class, false));
		self::assertNotNull($container->getByType(HttpServerSubscribers\HttpServer\Server::class, false));

		/**
		 * WS SERVER (Plugin/WsServer's own registrations) -- from WsServerExtensionTest
		 */

		self::assertNotNull($container->getByType(WsServerCommands\WsServer::class, false));
		self::assertNotNull($container->getByType(WsServerSubscribers\WsServer\Client::class, false));

		/**
		 * Domains none of the five surviving tests covered -- SimpleAuth, JSON:API, Phone and
		 * WebSockets never had a dedicated DI test before this merge.
		 */

		self::assertInstanceOf(
			SimpleAuthServices\SimpleAuth\Auth::class,
			$container->getService('fbCore.simpleAuth.auth'),
		);
		self::assertInstanceOf(
			JsonApiEncoding\JsonApi\Builder::class,
			$container->getService('fbCore.jsonApi.builder'),
		);
		self::assertInstanceOf(
			WebSocketsControllers\WebSockets\Controller\IControllerFactory::class,
			$container->getService('fbCore.webSockets.controllers.factory'),
		);
		self::assertNotNull($container->getByType(PhoneServices\PhoneNumberHelper::class, false));
		self::assertNotNull($container->getByType(PhoneSubscribers\PhoneObjectSubscriber::class, false));

		/**
		 * The service-key collision this task's own investigation found (Flagged
		 * Assumption 12), resolved by keying every service <domainTag>.<originalRelativeKey> --
		 * assert both halves of the collision survive as distinct services, not one silently
		 * overwriting the other. SimpleAuth and DoctrineTimestampable used to collide the same
		 * way over `configuration` -- the 2026-09-21 core cleanup merged those two into the one
		 * combined FastyBird\Core\Configuration\Configuration below, so this instead asserts
		 * that single service is reachable both by type and by its service name.
		 */

		$mergedConfiguration = $container->getService('fbCore.configuration');
		self::assertInstanceOf(Configuration\Configuration::class, $mergedConfiguration);
		self::assertSame($mergedConfiguration, $container->getByType(Configuration\Configuration::class, false));

		self::assertInstanceOf(
			PhoneSubscribers\PhoneObjectSubscriber::class,
			$container->getService('fbCore.phone.doctrinePhone.subscriber'),
		);
		self::assertInstanceOf(
			DoctrineTimestampableSubscribers\DoctrineTimestampable\TimestampableSubscriber::class,
			$container->getService('fbCore.doctrineTimestampable.subscriber'),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 */
	#[DoesNotPerformAssertions]
	public function testServicesRegistration(): void
	{
		$this->createContainer();
	}

}
