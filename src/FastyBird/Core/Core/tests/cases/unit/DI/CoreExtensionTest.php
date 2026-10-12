<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Api\Encoding;
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
use FastyBird\Core\Persistence\TimestampableConfiguration;
use FastyBird\Core\Phone\Services as PhoneServices;
use FastyBird\Core\Phone\Subscribers as PhoneSubscribers;
use FastyBird\Core\Security\Configuration;
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
use Symfony\Bridge\Monolog as BridgeMonolog;

/**
 * Compiles a container with CoreExtension and asserts that the services each of its capabilities
 * owns -- logging, documents, exchange, persistence, HTTP server, WebSocket server, security,
 * API and phone -- are registered, and that the service keys of the capabilities that would
 * otherwise collide stay distinct.
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
		 * LOGGING and DOCUMENTS
		 */

		self::assertNotNull($container->getByType(Monolog\Handler\RotatingFileHandler::class, false));
		self::assertNull($container->getByType(BridgeMonolog\Handler\ConsoleHandler::class, false));
		self::assertNotNull($container->getByType(Documents\DocumentFactory::class, false));
		self::assertInstanceOf(
			Documents\DocumentFactory::class,
			$container->getService('fbCore.documents.factory'),
		);

		/**
		 * EXCHANGE
		 */

		self::assertNotNull($container->getByType(Documents\RoutingDocumentFactory::class, false));
		self::assertNotNull($container->getByType(Publisher\Container::class, false));
		self::assertNotNull($container->getByType(Async\Container::class, false));
		self::assertNotNull($container->getByType(Consumers\Container::class, false));

		/**
		 * DATABASE HELPER and SCHEMA VALIDATION
		 */

		self::assertNotNull($container->getByType(Helpers\Database::class, false));
		self::assertNotNull($container->getByType(Schemas\Validator::class, false));

		/**
		 * HTTP SERVER
		 */

		self::assertNotNull($container->getByType(Server\Application::class, false));
		self::assertNotNull($container->getByType(HttpCommands\Server::class, false));
		self::assertNotNull($container->getByType(Http\ServerResponseFactory::class, false));
		self::assertNotNull($container->getByType(EventLoop\LoopInterface::class, false));
		self::assertNotNull($container->getByType(Middleware\Cors::class, false));
		self::assertNotNull($container->getByType(Middleware\StaticFiles::class, false));
		self::assertNotNull($container->getByType(Middleware\Router::class, false));
		self::assertNotNull($container->getByType(Server\Factory::class, false));
		self::assertNotNull($container->getByType(HttpSubscribers\Server::class, false));

		/**
		 * WEBSOCKET SERVER
		 */

		self::assertNotNull($container->getByType(WebSocketsCommands\Server::class, false));
		self::assertNotNull($container->getByType(WebSocketsSubscribers\Client::class, false));

		/**
		 * SECURITY, JSON:API, WEBSOCKET CONTROLLERS and PHONE
		 */

		self::assertInstanceOf(
			SecurityServices\Auth::class,
			$container->getService('fbCore.security.auth'),
		);
		self::assertInstanceOf(
			Encoding\Builder::class,
			$container->getService('fbCore.api.builder'),
		);
		self::assertInstanceOf(
			Controllers\ControllerFactory::class,
			$container->getService('fbCore.webSockets.controllers.factory'),
		);
		self::assertNotNull($container->getByType(PhoneServices\PhoneNumberHelper::class, false));
		self::assertNotNull($container->getByType(PhoneSubscribers\PhoneObjectSubscriber::class, false));

		/**
		 * Every service is keyed <domainTag>.<relativeKey>, so two capabilities that each register
		 * a `configuration` service cannot overwrite one another. Security\Configuration and
		 * Persistence\TimestampableConfiguration are each registered by their owning capability,
		 * so this asserts each is reachable both by type and by its service name.
		 */

		$securityConfiguration = $container->getService('fbCore.security.configuration');
		self::assertInstanceOf(Configuration::class, $securityConfiguration);
		self::assertSame($securityConfiguration, $container->getByType(Configuration::class, false));

		$timestampableConfiguration = $container->getService('fbCore.persistence.timestampable.configuration');
		self::assertInstanceOf(TimestampableConfiguration::class, $timestampableConfiguration);
		self::assertSame(
			$timestampableConfiguration,
			$container->getByType(TimestampableConfiguration::class, false),
		);

		self::assertInstanceOf(
			PhoneSubscribers\PhoneObjectSubscriber::class,
			$container->getService('fbCore.phone.doctrine.subscriber'),
		);
		self::assertInstanceOf(
			PersistenceSubscribers\TimestampableSubscriber::class,
			$container->getService('fbCore.persistence.timestampable.subscriber'),
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
