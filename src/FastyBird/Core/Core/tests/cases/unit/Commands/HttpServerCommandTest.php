<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Commands;

use FastyBird\Core\Commands;
use FastyBird\Core\Middleware\WebServer as Middleware;
use FastyBird\Core\Server\HttpServer as Server;
use FastyBird\Core\Values\Types\Sources;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function in_array;

final class HttpServerCommandTest extends TestCase
{

	/**
	 * @throws Console\Exception\LogicException
	 * @throws Console\Exception\CommandNotFoundException
	 */
	public function testExecute(): void
	{
		$promise = $this->createMock(Promise\PromiseInterface::class);
		$promise
			->method('then')
			->willReturn($promise);

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger
			->expects(self::exactly(2))
			->method('info')
			->with(
				self::callback(static function (...$args): bool {
					$valid = [
						[
							'Starting HTTP Server',
							[
								'source' => Sources\Plugin::WEB_SERVER->value,
								'type' => 'server-command',
							],
						],
						[
							'Listening on "http://127.0.0.1:8001"',
							[
								'source' => Sources\Plugin::WEB_SERVER->value,
								'type' => 'factory',
							],
						],
					];

					return in_array($args, $valid, true);
				}),
			);

		$eventLoop = $this->createMock(EventLoop\LoopInterface::class);
		$eventLoop
			->method('addReadStream');
		$eventLoop
			->method('run');

		$eventDispatcher = $this->createMock(EventDispatcher\EventDispatcherInterface::class);
		$eventDispatcher
			->expects(self::exactly(1))
			->method('dispatch');

		$corsMiddleware = $this->createMock(Middleware\Cors::class);

		$staticFilesMiddleware = $this->createMock(Middleware\StaticFiles::class);

		$routerMiddleware = $this->createMock(Middleware\Router::class);

		$serverFactory = new Server\Factory(
			$corsMiddleware,
			$staticFilesMiddleware,
			$routerMiddleware,
			$eventLoop,
			$logger,
		);

		$application = new Application();
		$application->add(new Commands\HttpServer(
			'127.0.0.1',
			8_001,
			$serverFactory,
			$eventLoop,
			$eventDispatcher,
			$logger,
		));

		$command = $application->get(Commands\HttpServer::NAME);

		$commandTester = new CommandTester($command);
		$commandTester->execute([]);
	}

}
