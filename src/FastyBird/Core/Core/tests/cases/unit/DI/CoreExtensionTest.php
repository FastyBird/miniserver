<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Configuration;
use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Commands as HttpCommands;
use FastyBird\Core\Http\Middleware;
use FastyBird\Core\Http\Server;
use FastyBird\Core\Http\Subscribers as HttpSubscribers;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Subscribers as PersistenceSubscribers;
use FastyBird\Core\Phone\Services as PhoneServices;
use FastyBird\Core\Phone\Subscribers as PhoneSubscribers;
use FastyBird\Core\Security\Services as SecurityServices;
use FastyBird\Core\Tests;
use FastyBird\Core\Values\Schemas;
use FastyBird\Core\WebSockets\Commands as WebSocketsCommands;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Subscribers as WebSocketsSubscribers;
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
		self::assertNotNull($container->getByType(Documents\DocumentFactory::class, false));
		self::assertInstanceOf(
			Documents\DocumentFactory::class,
			$container->getService('document.factory'),
		);

		/**
		 * EXCHANGE -- from ExchangeExtensionTest
		 */

		self::assertNotNull($container->getByType(Documents\RoutingDocumentFactory::class, false));
		self::assertNotNull($container->getByType(Publisher\Container::class, false));
		self::assertNotNull($container->getByType(Async\Container::class, false));
		self::assertNotNull($container->getByType(Consumers\Container::class, false));

		/**
		 * TOOLS -- from ToolsExtensionTest
		 */

		self::assertNotNull($container->getByType(Helpers\Database::class, false));
		self::assertNotNull($container->getByType(Schemas\Validator::class, false));

		/**
		 * HTTP SERVER (formerly Plugin/WebServer) -- from WebServerExtensionTest
		 */

		self::assertNotNull($container->getByType(Server\Application::class, false));
		self::assertNotNull($container->getByType(HttpCommands\HttpServer::class, false));
		self::assertNotNull($container->getByType(Http\ServerResponseFactory::class, false));
		self::assertNotNull($container->getByType(EventLoop\LoopInterface::class, false));
		self::assertNotNull($container->getByType(Middleware\Cors::class, false));
		self::assertNotNull($container->getByType(Middleware\StaticFiles::class, false));
		self::assertNotNull($container->getByType(Middleware\Router::class, false));
		self::assertNotNull($container->getByType(Server\Factory::class, false));
		self::assertNotNull($container->getByType(HttpSubscribers\Server::class, false));

		/**
		 * WS SERVER (Plugin/WsServer's own registrations) -- from WsServerExtensionTest
		 */

		self::assertNotNull($container->getByType(WebSocketsCommands\WsServer::class, false));
		self::assertNotNull($container->getByType(WebSocketsSubscribers\Client::class, false));

		/**
		 * Domains none of the five surviving tests covered -- SimpleAuth, JSON:API, Phone and
		 * WebSockets never had a dedicated DI test before this merge.
		 */

		self::assertInstanceOf(
			SecurityServices\Auth::class,
			$container->getService('fbCore.simpleAuth.auth'),
		);
		self::assertInstanceOf(
			Encoding\Builder::class,
			$container->getService('fbCore.jsonApi.builder'),
		);
		self::assertInstanceOf(
			Controllers\IControllerFactory::class,
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
		 * combined FastyBird\Core\Configuration below, so this instead asserts
		 * that single service is reachable both by type and by its service name.
		 */

		$mergedConfiguration = $container->getService('fbCore.configuration');
		self::assertInstanceOf(Configuration::class, $mergedConfiguration);
		self::assertSame($mergedConfiguration, $container->getByType(Configuration::class, false));

		self::assertInstanceOf(
			PhoneSubscribers\PhoneObjectSubscriber::class,
			$container->getService('fbCore.phone.doctrinePhone.subscriber'),
		);
		self::assertInstanceOf(
			PersistenceSubscribers\TimestampableSubscriber::class,
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
