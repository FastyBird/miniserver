<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Support;

use BinSoul\Net\Mqtt;
use React\EventLoop;
use React\Socket;
use function assert;
use function count;
use function explode;
use function intval;
use function is_string;

/**
 * Minimal MQTT broker on the loopback interface, driven by the test's event loop
 *
 * It accepts a connection, acknowledges connect, subscribe and unsubscribe, records the messages the
 * client published, closes the connection on disconnect, and can push packets to the client on demand.
 */
final class FakeBroker
{

	private Socket\SocketServer $server;

	private Socket\ConnectionInterface|null $connection = null;

	/** @var array<int, array{string, string}> */
	private array $published = [];

	public function __construct(EventLoop\LoopInterface $eventLoop)
	{
		$this->server = new Socket\SocketServer('127.0.0.1:0', [], $eventLoop);

		$this->server->on('connection', function (Socket\ConnectionInterface $connection): void {
			$this->connection = $connection;

			$parser = new Mqtt\StreamParser(new Mqtt\DefaultPacketFactory());

			$connection->on('data', function (string $data) use ($connection, $parser): void {
				foreach ($parser->push($data) as $packet) {
					$this->handle($connection, $packet);
				}
			});
		});
	}

	public function getPort(): int
	{
		$address = $this->server->getAddress();
		assert(is_string($address));

		$parts = explode(':', $address);

		return intval($parts[count($parts) - 1]);
	}

	/**
	 * @return array<int, array{string, string}> topic and payload of every message the client published
	 */
	public function getPublished(): array
	{
		return $this->published;
	}

	public function pushMessage(string $topic, string $payload): void
	{
		$packet = new Mqtt\Packet\PublishRequestPacket();
		$packet->setTopic($topic);
		$packet->setPayload($payload);
		$packet->setQosLevel(0);

		$this->connection?->write((string) $packet);
	}

	/**
	 * A ping response nobody asked for, which the client reports as a warning
	 */
	public function pushUnexpectedPingResponse(): void
	{
		$this->connection?->write((string) new Mqtt\Packet\PingResponsePacket());
	}

	public function close(): void
	{
		$this->connection?->close();
		$this->server->close();
	}

	private function handle(Socket\ConnectionInterface $connection, Mqtt\Packet $packet): void
	{
		switch ($packet->getPacketType()) {
			case Mqtt\Packet::TYPE_CONNECT:
				$response = new Mqtt\Packet\ConnectResponsePacket();
				$response->setReturnCode(0);

				$connection->write((string) $response);

				break;
			case Mqtt\Packet::TYPE_PUBLISH:
				assert($packet instanceof Mqtt\Packet\PublishRequestPacket);

				$this->published[] = [$packet->getTopic(), $packet->getPayload()];

				break;
			case Mqtt\Packet::TYPE_SUBSCRIBE:
				assert($packet instanceof Mqtt\Packet\SubscribeRequestPacket);

				$response = new Mqtt\Packet\SubscribeResponsePacket();
				$response->setIdentifier($packet->getIdentifier());
				$response->setReturnCodes([0]);

				$connection->write((string) $response);

				break;
			case Mqtt\Packet::TYPE_UNSUBSCRIBE:
				assert($packet instanceof Mqtt\Packet\UnsubscribeRequestPacket);

				$response = new Mqtt\Packet\UnsubscribeResponsePacket();
				$response->setIdentifier($packet->getIdentifier());

				$connection->write((string) $response);

				break;
			case Mqtt\Packet::TYPE_DISCONNECT:
				$connection->end();

				break;
		}
	}

}
