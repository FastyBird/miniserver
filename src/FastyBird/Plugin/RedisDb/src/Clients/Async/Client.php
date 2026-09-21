<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 * @since          1.0.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Clients\Async;

use Clue\React\Redis;
use FastyBird\Core\Events\Exchange as ExchangeEvents;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Events;
use InvalidArgumentException;
use Nette;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;
use function strval;

/**
 * Redis DB async client factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client
{

	use Nette\SmartObject;

	private int $selectedDatabase = 0;

	private Redis\RedisClient|null $redis = null;

	public function __construct(
		private readonly Connections\Configuration $connection,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<string|null>
	 *
	 * @throws InvalidArgumentException
	 */
	public function get(string $key): Promise\PromiseInterface
	{
		return $this->getClient()->get($key);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws InvalidArgumentException
	 */
	public function set(string $key, string $content): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getClient()->set($key, $content)
			->then(static function ($result) use ($deferred): void {
				$deferred->resolve($result === 'OK');
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws InvalidArgumentException
	 */
	public function del(string $key): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getClient()->del($key)
			->then(static function ($result) use ($deferred): void {
				$deferred->resolve(strval($result) === '1' || strval($result) === '0');
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws InvalidArgumentException
	 */
	public function publish(string $channel, string $content): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getClient()->publish($channel, $content)
			->then(static function () use ($deferred): void {
				$deferred->resolve(true);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function select(int $database): void
	{
		if ($this->selectedDatabase !== $database) {
			$this->getClient()->select($database);

			$this->selectedDatabase = $database;
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function getClient(): Redis\RedisClient
	{
		if ($this->redis === null) {
			$this->redis = new Redis\RedisClient(
				$this->connection->getHost() . ':' . $this->connection->getPort(),
				new Socket\Connector($this->eventLoop),
			);

			$this->redis->on('close', function (): void {
				$this->dispatcher?->dispatch(new Events\ConnectionClosed());
			});

			$this->redis->on('error', function (Throwable $ex): void {
				$this->dispatcher?->dispatch(new ExchangeEvents\ExchangeError($ex));
			});
		}

		return $this->redis;
	}

}
