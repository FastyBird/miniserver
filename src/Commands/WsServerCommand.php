<?php declare(strict_types = 1);

/**
 * WsServerCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\MiniServer\Commands;

use FastyBird\SocketServerFactory;
use IPub\WebSockets;
use Nette;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * HTTP server command
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsServerCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	/** @var WebSockets\Server\Handlers */
	private WebSockets\Server\Handlers $handlers;

	/** @var WebSockets\Server\Configuration */
	private WebSockets\Server\Configuration $configuration;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/** @var SocketServerFactory\SocketServerFactory */
	private SocketServerFactory\SocketServerFactory $socketServerFactory;

	/** @var EventLoop\LoopInterface */
	private EventLoop\LoopInterface $eventLoop;

	/**
	 * @param WebSockets\Server\Handlers $handlers
	 * @param WebSockets\Server\Configuration $configuration
	 * @param SocketServerFactory\SocketServerFactory $socketServerFactory
	 * @param EventLoop\LoopInterface $eventLoop
	 * @param Log\LoggerInterface|null $logger
	 * @param string|null $name
	 */
	public function __construct(
		WebSockets\Server\Handlers $handlers,
		WebSockets\Server\Configuration $configuration,
		SocketServerFactory\SocketServerFactory $socketServerFactory,
		EventLoop\LoopInterface $eventLoop,
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->handlers = $handlers;
		$this->configuration = $configuration;

		$this->socketServerFactory = $socketServerFactory;

		$this->logger = $logger ?? new Log\NullLogger();

		$this->eventLoop = $eventLoop;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName('fb:ws-server:start')
			->setDescription('Start WS server.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output
	): int {
		$this->logger->info(
			'Starting WS server',
			[
				'source'   => 'server-command',
				'type'     => 'start',
			]
		);

		$socketServer = $this->socketServerFactory->create();

		try {
			$socketServer->on('connection', function (Socket\ConnectionInterface $connection): void {
				$this->handlers->handleConnect($connection);
			});

			$socketServer->on('error', function (Throwable $ex): void {
				$this->logger->error('Could not establish connection', [
					'source'    => 'ws-server-plugin-server',
					'type'      => 'start',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);
			});

			$this->eventLoop->run();
		} catch (WebSockets\Exceptions\TerminateException $ex) {
			// Log error action reason
			$this->logger->error(
				'WS server was forced to close',
				[
					'source'   => 'server-command',
					'type'     => 'terminate',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping WS server',
				[
					'source'   => 'server-command',
					'type'     => 'process',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
					'cmd'       => $this->getName(),
				]
			);

			$this->eventLoop->stop();
		}

		return 0;
	}

}
