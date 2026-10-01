<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Commands;

use FastyBird\Core\Http\Commands;
use FastyBird\Core\Http\Middleware;
use FastyBird\Core\Http\Server;
use FastyBird\Core\Values\Types\Sources;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function count;
use function is_string;
use function preg_match;

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
					if ($args === [
						'Starting HTTP Server',
						[
							'source' => Sources\Plugin::WEB_SERVER->value,
							'type' => 'server-command',
						],
					]) {
						return true;
					}

					// The command binds port 0, so the operating system picks a free port and
					// the test never competes with anything else for a fixed one.
					return count($args) === 2
						&& is_string($args[0])
						&& preg_match('#^Listening on "http://127\.0\.0\.1:[1-9][0-9]*"$#', $args[0]) === 1
						&& $args[1] === [
							'source' => Sources\Plugin::WEB_SERVER->value,
							'type' => 'factory',
						];
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
			0,
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
