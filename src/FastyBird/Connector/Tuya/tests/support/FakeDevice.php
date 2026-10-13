<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Support;

use React\EventLoop;
use React\Socket;
use RuntimeException;

/**
 * Stand-in for a Tuya device on the loopback interface, driven by the test's event loop
 *
 * It accepts the one connection the client under test opens, hands it to the test, and sends or closes on demand.
 */
final class FakeDevice
{

	private Socket\SocketServer $server;

	private Socket\ConnectionInterface|null $connection = null;

	/**
	 * @throws RuntimeException when the port is taken
	 */
	public function __construct(EventLoop\LoopInterface $eventLoop, int $port)
	{
		$this->server = new Socket\SocketServer('127.0.0.1:' . $port, [], $eventLoop);

		$this->server->on('connection', function (Socket\ConnectionInterface $connection): void {
			$this->connection = $connection;
		});
	}

	public function hasClient(): bool
	{
		return $this->connection !== null;
	}

	public function send(string $data): void
	{
		$this->connection?->write($data);
	}

	public function closeConnection(): void
	{
		$this->connection?->close();
		$this->connection = null;
	}

	public function close(): void
	{
		$this->closeConnection();

		$this->server->close();
	}

}
