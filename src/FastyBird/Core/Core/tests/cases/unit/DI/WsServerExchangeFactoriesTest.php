<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use FastyBird\Core\WebSockets\Commands;
use Nette;
use ReflectionException;
use ReflectionProperty;

/**
 * The fb:ws-server:start command starts every exchange registered in the container (#566).
 *
 * WsServer::execute() calls create() on each of its exchangeFactories, and nothing else starts
 * an exchange in the WS server process. The overlay registers the factory under services:, which
 * nette/di processes after every extension's loadConfiguration(), the same position as RedisDb
 * or RabbitMQ registered in config/local.neon.
 */
final class WsServerExchangeFactoriesTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testCommandReceivesAnExchangeFactoryRegisteredAfterCore(): void
	{
		$container = $this->createContainer(__DIR__ . '/wsServerExchangeFactory.neon');

		$exchangeFactory = $container->getByType(Dummy\DummyExchangeFactory::class);
		$command = $container->getByType(Commands\WsServer::class);

		$exchangeFactories = (new ReflectionProperty(Commands\WsServer::class, 'exchangeFactories'))
			->getValue($command);

		// Keyed by service name, as ContainerBuilder::findByType() returns them
		self::assertSame(['dummyExchangeFactory' => $exchangeFactory], $exchangeFactories);
	}

}
