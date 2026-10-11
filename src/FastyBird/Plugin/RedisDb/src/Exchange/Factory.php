<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Exchange;

use Clue\React\Redis;
use FastyBird\Core\Exchange;
use FastyBird\Core\Exchange\Events as ExchangeEvents;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use InvalidArgumentException;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Socket;
use Throwable;

/**
 * Redis DB exchange factory
 */
final readonly class Factory implements Exchange\Factory
{

	public function __construct(
		private string $channel,
		private Connections\Configuration $connection,
		private Handler $messagesHandler,
		private EventLoop\LoopInterface|null $eventLoop = null,
		private EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function create(): void
	{
		$redis = new Redis\RedisClient(
			$this->connection->getHost() . ':' . $this->connection->getPort(),
			new Socket\Connector($this->eventLoop),
		);

		$redis->on('close', function (): void {
			$this->dispatcher?->dispatch(new RedisDbEvents\ConnectionClosed());
		});

		$redis->on('error', function (Throwable $ex): void {
			$this->dispatcher?->dispatch(new ExchangeEvents\ExchangeError($ex));
		});

		$redis->on('message', function (string $channel, string $payload): void {
			if ($channel === $this->channel) {
				$this->messagesHandler->handle($payload);
			}
		});

		$redis->subscribe($this->channel);
	}

}
