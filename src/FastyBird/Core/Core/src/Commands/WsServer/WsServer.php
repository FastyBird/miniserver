<?php declare(strict_types = 1);

/**
 * WsServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           09.06.22
 */

namespace FastyBird\Core\Commands\WsServer;

use FastyBird\Core\Events;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Messaging\Exchange as ExchangeExchange;
use FastyBird\Core\Server\WsServer as Server;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
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
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsServer extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:ws-server:start';

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Server\Configuration $configuration,
		private readonly Server\Server $server,
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
				'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
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
						'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
						'type' => 'server-command',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);
			});

			$this->server->create($socketServer);

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}

			$this->eventLoop->run();

		} catch (WebSocketsExceptions\Terminate $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
					'type' => 'server-command',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
					'type' => 'server-command',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
