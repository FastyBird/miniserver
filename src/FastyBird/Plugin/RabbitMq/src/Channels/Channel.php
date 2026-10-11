<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Channels;

use Bunny;
use FastyBird\Plugin\RabbitMq\Connections;
use Nette;
use Throwable;
use function assert;

/**
 * Rabbit MQ client
 */
class Channel
{

	use Nette\SmartObject;

	private Bunny\Client|null $bunny = null;

	private Bunny\Channel|null $channel = null;

	public function __construct(
		private readonly Connections\Connection $connection,
	)
	{
	}

	/**
	 * @param array<string, string> $headers
	 */
	public function publish(string $body, array $headers, string $exchange, string $routingKey): bool|int
	{
		$bunny = $this->getClient();

		if (!$bunny->isConnected()) {
			try {
				$bunny->connect();
			} catch (Throwable) {
				return false;
			}
		}

		if ($this->channel === null) {
			$channel = $bunny->channel();
			assert($channel instanceof Bunny\Channel);

			$this->channel = $channel;
		}

		return $this->channel->publish($body, $headers, $exchange, $routingKey);
	}

	private function getClient(): Bunny\Client
	{
		if ($this->bunny === null) {
			$this->bunny = new Bunny\Client([
				'host' => $this->connection->getHost(),
				'port' => $this->connection->getPort(),
				'vhost' => $this->connection->getVhost(),
				'user' => $this->connection->getUsername(),
				'password' => $this->connection->getPassword(),
				'heartbeat' => 30,
			]);
		}

		return $this->bunny;
	}

}
