<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\HttpServer;

use FastyBird\Core\Logging;
use FastyBird\Core\Middleware\WebServer as Middleware;
use FastyBird\Core\Values\Types\Sources;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Socket;
use Throwable;
use function sprintf;
use function str_replace;

/**
 * HTTP server factory
 */
final readonly class Factory
{

	public function __construct(
		private Middleware\Cors $corsMiddleware,
		private Middleware\StaticFiles $staticFilesMiddleware,
		private Middleware\Router $routerMiddleware,
		private EventLoop\LoopInterface $eventLoop,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function create(Socket\ServerInterface $server): void
	{
		$httpServer = new Http\HttpServer(
			$this->eventLoop,
			$this->corsMiddleware,
			$this->staticFilesMiddleware,
			$this->routerMiddleware,
		);

		$httpServer->on('error', function (Throwable $ex): void {
			// Log error action reason
			$this->logger->error(
				'An error occurred during handling request. Stopping HTTP server',
				[
					'source' => Sources\Plugin::WEB_SERVER->value,
					'type' => 'factory',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$this->eventLoop->stop();
		});

		$httpServer->listen($server);

		if ($server->getAddress() !== null) {
			if ($server instanceof Socket\SecureServer) {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tls:', 'https:', $server->getAddress())),
					[
						'source' => Sources\Plugin::WEB_SERVER->value,
						'type' => 'factory',
					],
				);

			} else {
				$this->logger->info(
					sprintf('Listening on "%s"', str_replace('tcp:', 'http:', $server->getAddress())),
					[
						'source' => Sources\Plugin::WEB_SERVER->value,
						'type' => 'factory',
					],
				);
			}
		}
	}

}
