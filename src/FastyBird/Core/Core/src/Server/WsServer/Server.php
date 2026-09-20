<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use Nette;
use Nette\Utils;
use Psr\Log;
use React;
use React\EventLoop;
use Throwable;
use function parse_url;
use function property_exists;
use function sprintf;

/**
 * WebSocket server
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @method onCreate(Server $server)
 * @method onStart(EventLoop\LoopInterface $loop, Server $server)
 * @method onStop(EventLoop\LoopInterface $loop, Server $server)
 */
final class Server
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public const VERSION = 'IPub/WebSockets/1.0.0';

	public array $onCreate = [];

	public array $onStart = [];

	public array $onStop = [];

	private Log\LoggerInterface|Log\NullLogger|null $logger = null;

	public function __construct(
		private Handlers $handlers,
		private EventLoop\LoopInterface $loop,
		private Configuration $configuration,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * Run IO server
	 */
	public function create(
		React\Socket\SocketServer|null $socket = null,
		React\Socket\SocketServer|null $flashSocket = null,
	): void
	{
		$client = $this->configuration->getAddress() . ':' . $this->configuration->getPort();

		if ($socket === null) {
			$socket = new React\Socket\SocketServer($client, [], $this->loop);

			if ($this->configuration->isSslEnabled()) {
				$socket = new React\Socket\SecureServer(
					$socket,
					$this->loop,
					$this->configuration->getSslConfiguration(),
				);
			}
		}

		$socket->on('connection', function (React\Socket\ConnectionInterface $connection): void {
			if ($connection->getLocalAddress() === null) {
				return;
			}

			$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

			if (
				property_exists($parsed, 'port')
				&& $parsed->offsetGet('port') === $this->configuration->getPort()
			) {
				$this->handlers->handleConnect($connection);
			}
		});

		$socket->on('error', function (Throwable $ex): void {
			$this->logger->error('Could not establish connection: ' . $ex->getMessage());
		});

		$flashPort = 8_843;

		if ($this->configuration->getPort() === 80) {
			$flashPort = 843;
		}

		if ($flashSocket === null) {
			$flashClient = $this->configuration->getPort() === 80
				? '0.0.0.0:' . $flashPort
				: $this->configuration->getAddress() . ':' . $flashPort;

			$flashSocket = new React\Socket\SocketServer($flashClient, [], $this->loop);
		}

		$flashSocket->on('connection', function (React\Socket\ConnectionInterface $connection) use ($flashPort): void {
			if ($connection->getLocalAddress() === null) {
				return;
			}

			$parsed = Utils\ArrayHash::from((array) parse_url($connection->getLocalAddress()));

			if (
				property_exists($parsed, 'port')
				&& $parsed->offsetGet('port') === $flashPort
			) {
				$this->handlers->handleFlashConnect($connection);
			}
		});

		$flashSocket->on('error', function (Throwable $ex): void {
			$this->logger->error('Could not establish connection: ' . $ex->getMessage());
		});

		$this->onCreate($this);
	}

	public function run(): void
	{
		$this->onStart($this->loop, $this);

		$this->logger->debug('Starting FastyBird\Core\Server\WsServer');
		$this->logger->debug(
			sprintf(
				'Launching WebSockets WS Server on: %s:%s',
				$this->configuration->getAddress(),
				$this->configuration->getPort(),
			),
		);

		$this->loop->run();
	}

	public function stop(): void
	{
		$this->onStop($this->loop, $this);

		$this->loop->stop();
	}

}
