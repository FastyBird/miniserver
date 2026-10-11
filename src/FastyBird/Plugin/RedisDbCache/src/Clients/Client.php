<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Clients;

use FastyBird\Plugin\RedisDbCache\Connections;
use Nette;
use Predis;
use Predis\Response as PredisResponse;
use function assert;

/**
 * Redis DB sync client to PREDIS instance
 */
class Client
{

	use Nette\SmartObject;

	private int $selectedDatabase = 0;

	/** @var Predis\Client<mixed>|null */
	private Predis\Client|null $redis = null;

	/** @var array<string, int|string|null> */
	private array $options;

	public function __construct(Connections\Configuration $connection)
	{
		$this->options = [
			'scheme' => 'tcp',
			'host' => $connection->getHost(),
			'port' => $connection->getPort(),
			'database' => $connection->getDatabase(),
		];

		if ($connection->getUsername() !== null) {
			$this->options['username'] = $connection->getUsername();
		}

		if ($connection->getPassword() !== null) {
			$this->options['password'] = $connection->getPassword();
		}
	}

	public function exists(string $key): bool
	{
		$response = $this->getClient()->exists($key);

		return $response === 1;
	}

	/**
	 * @return array<string>
	 */
	public function keys(string $pattern): array
	{
		return $this->getClient()->keys($pattern);
	}

	public function multi(): mixed
	{
		return $this->getClient()->multi();
	}

	/**
	 * @return array<mixed>|null
	 */
	public function exec(): array|null
	{
		return $this->getClient()->exec();
	}

	public function expire(string $key, int $seconds): bool
	{
		$response = $this->getClient()->expire($key, $seconds);

		return $response === 1 || $response === 0;
	}

	public function get(string $key): string|null
	{
		$response = $this->getClient()->get($key);

		if ($response instanceof PredisResponse\ResponseInterface) {
			return null;
		}

		return $response;
	}

	/**
	 * @param array<string> $keys
	 *
	 * @return array<string|null>
	 */
	public function mGet(array $keys): array
	{
		return $this->getClient()->mget($keys);
	}

	public function set(string $key, string $content): bool
	{
		$response = $this->getClient()->set($key, $content);
		assert($response instanceof PredisResponse\Status);

		return $response->getPayload() === 'OK';
	}

	public function setEx(string $key, int $seconds, string $content): bool
	{
		$response = $this->getClient()->setex($key, $seconds, $content);

		return $response === 1;
	}

	/**
	 * @param array<string> $members
	 */
	public function sAdd(string $key, array $members): bool
	{
		$response = $this->getClient()->sadd($key, $members);

		return $response === 1;
	}

	/**
	 * @return array<string>
	 */
	public function sMembers(string $key): array
	{
		return $this->getClient()->smembers($key);
	}

	public function sRem(string $key, string $member): bool
	{
		$response = $this->getClient()->srem($key, $member);

		return $response === 1 || $response === 0;
	}

	/**
	 * @param array<mixed> $membersAndScoresDictionary
	 */
	public function zAdd(string $key, array $membersAndScoresDictionary): bool
	{
		$response = $this->getClient()->zadd($key, $membersAndScoresDictionary);

		return $response === 1 || $response === 0;
	}

	public function zRem(string $key, string $member): bool
	{
		$response = $this->getClient()->zrem($key, $member);

		return $response === 1 || $response === 0;
	}

	/**
	 * @param string|array<string> $key
	 */
	public function del(string|array $key): bool
	{
		$response = $this->getClient()->del($key);

		return $response === 1 || $response === 0;
	}

	/**
	 * @param array<string>|null $options
	 *
	 * @return array<string>
	 */
	public function zRangeByScore(string $key, int $min, int $max, array|null $options = null): array
	{
		return $this->getClient()->zrangebyscore($key, $min, $max, $options ?? []);
	}

	public function flushDb(): void
	{
		$this->getClient()->flushdb();
	}

	public function select(int $database): void
	{
		if ($this->selectedDatabase !== $database) {
			$this->getClient()->select($database);

			$this->selectedDatabase = $database;
		}
	}

	public function getClient(): Predis\Client
	{
		if ($this->redis === null) {
			$this->redis = new Predis\Client($this->options);
		}

		return $this->redis;
	}

}
