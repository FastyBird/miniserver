<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Commands;

use FastyBird\Core\Exchange;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions;
use FastyBird\Core\WebSockets\Server;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * WS server command
 */
final class WsServer extends Console\Command\Command
{

	public const string NAME = 'fb:ws-server:start';

	/**
	 * @param array<Exchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Server\Configuration $configuration,
		private readonly Server\ServerRuntime $server,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly array $exchangeFactories = [],
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('WebSockets server service');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Starting WS server',
			[
				'source' => Sources\Plugin::WS_SERVER->value,
				'type' => 'server-command',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\WsServerStartup());

			$socketServer = new Socket\SocketServer(
				$this->configuration->getAddress() . ':' . $this->configuration->getPort(),
				[],
				$this->eventLoop,
			);

			$socketServer->on('error', function (Throwable $ex): void {
				$this->dispatcher?->dispatch(new Events\WsServerError($ex));

				$this->logger->error(
					'An error occurred during handling requests. Stopping WS server',
					[
						'source' => Sources\Plugin::WS_SERVER->value,
						'type' => 'server-command',
						'exception' => Logging\Logger::buildException($ex),
					],
				);
			});

			$this->server->create($socketServer);

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}

			$this->eventLoop->run();

		} catch (Exceptions\Terminate $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source' => Sources\Plugin::WS_SERVER->value,
					'type' => 'server-command',
					'exception' => Logging\Logger::buildException($ex),
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source' => Sources\Plugin::WS_SERVER->value,
					'type' => 'server-command',
					'exception' => Logging\Logger::buildException($ex),
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
