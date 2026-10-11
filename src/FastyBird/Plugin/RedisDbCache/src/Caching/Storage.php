<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Caching;

use FastyBird\Plugin\RedisDbCache\Clients;
use FastyBird\Plugin\RedisDbCache\Exceptions;
use JsonException;
use Nette;
use Nette\Caching;
use Nette\Utils;
use Override;
use Predis;
use function array_map;
use function assert;
use function explode;
use function is_numeric;
use function is_string;
use function json_encode;
use function microtime;
use function serialize;
use function str_replace;
use function time;
use function unserialize;

/**
 * Redis cache storage
 */
final class Storage implements Caching\Storage
{

	use Nette\SmartObject;

	public const string NS_PREFIX = 'FB.Storage';

	private const string NS_SEPARATOR = "\x00";

	private const string META_TIME = 'time'; // timestamp

	private const string META_EXPIRE = 'expire'; // expiration timestamp

	private const string META_DELTA = 'delta'; // relative (sliding) expiration

	private const string META_ITEMS = 'di'; // array of dependent items (file => timestamp)

	private const string META_CALLBACKS = 'callbacks'; // array of callbacks (function, args)

	private const string KEY = 'key'; // additional cache structure

	public function __construct(
		private readonly Clients\Client $client,
		private readonly Caching\Storages\Journal|null $journal = null,
	)
	{
	}

	/**
	 * Read from cache
	 */
	#[Override]
	public function read(string $key)
	{
		$stored = $this->doRead($key);

		if ($stored === null || !$this->verify($stored->getMeta())) {
			return null;
		}

		return @unserialize($stored->getData());
	}

	/**
	 * Read multiple entries from cache (using mget)
	 *
	 * @param array<string> $keys
	 *
	 * @return array<mixed>
	 */
	public function multiRead(array $keys): array
	{
		$values = [];

		foreach ($this->doMultiRead($keys) as $key => $stored) {
			$values[$key] = null;

			if ($stored !== null && $this->verify($stored->getMeta())) {
				$values[$key] = @unserialize($stored->getData());
			}
		}

		return $values;
	}

	#[Override]
	public function lock(string $key): void
	{
		// unsupported now
	}

	/**
	 * Writes item into the cache
	 *
	 * @param array<mixed> $dependencies
	 *
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function write(string $key, mixed $data, array $dependencies): void
	{
		$meta = [
			self::META_TIME => microtime(),
		];

		if (isset($dependencies[Caching\Cache::Expire]) && is_numeric($dependencies[Caching\Cache::Expire])) {
			if (isset($dependencies[Caching\Cache::Sliding]) && $dependencies[Caching\Cache::Sliding] === true) {
				$meta[self::META_EXPIRE] = $dependencies[Caching\Cache::Expire] + time(); // absolute time

			} else {
				$meta[self::META_DELTA] = (int) $dependencies[Caching\Cache::Expire]; // sliding time
			}
		}

		if (isset($dependencies[Caching\Cache::Items])) {
			foreach ((array) $dependencies[Caching\Cache::Items] as $itemName) {
				assert(is_string($itemName));

				$m = $this->readMeta($itemName);

				$meta[self::META_ITEMS][$itemName] = $m[self::META_TIME] ?? null; // may be null

				unset($m);
			}
		}

		if (isset($dependencies[Caching\Cache::Callbacks]) && $dependencies[Caching\Cache::Callbacks] !== []) {
			$meta[self::META_CALLBACKS] = $dependencies[Caching\Cache::Callbacks];
		}

		$cacheKey = $this->formatEntryKey($key);

		if (isset($dependencies[Caching\Cache::Tags]) || isset($dependencies[Caching\Cache::Priority])) {
			if ($this->journal === null) {
				throw new Exceptions\InvalidState('Cache journal has not been provided');
			}

			$this->journal->write($cacheKey, $dependencies);
		}

		$data = @serialize($data);

		$store = json_encode($meta) . self::NS_SEPARATOR . $data;

		try {
			if (isset($dependencies[Caching\Cache::Expire]) && is_numeric($dependencies[Caching\Cache::Expire])) {
				$this->client->setEx($cacheKey, (int) $dependencies[Caching\Cache::Expire], $store);

			} else {
				$this->client->set($cacheKey, $store);
			}
		} catch (Predis\PredisException $ex) {
			$this->remove($key);

			throw new Exceptions\InvalidState($ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * Removes item from the cache
	 */
	#[Override]
	public function remove(string $key): void
	{
		$this->client->del($this->formatEntryKey($key));

		if ($this->journal instanceof Journal) {
			$this->journal->cleanEntry($this->formatEntryKey($key));
		}
	}

	/**
	 * Removes items from the cache by conditions & garbage collector.
	 *
	 * @param array<mixed> $conditions
	 */
	#[Override]
	public function clean(array $conditions): void
	{
		// Cleaning using file iterator
		if (isset($conditions[Caching\Cache::All])) {
			$this->client->flushDb();

			return;
		}

		// Cleaning using journal
		if ($this->journal !== null) {
			$keys = $this->journal->clean($conditions);

			if ($keys !== null && $keys !== []) {
				$this->client->del($keys);
			}
		}
	}

	private function formatEntryKey(string $key): string
	{
		return self::NS_PREFIX . ':' . str_replace(self::NS_SEPARATOR, ':', $key);
	}

	/**
	 * Verifies dependencies.
	 *
	 * @param array<mixed> $meta
	 */
	private function verify(array $meta): bool
	{
		// Check if META_DELTA is not empty and set expiration
		if (isset($meta[self::META_DELTA]) && is_numeric($meta[self::META_DELTA])) {
			$this->client->expire($this->formatEntryKey($meta[self::KEY]), (int) $meta[self::META_DELTA]);

			return true;
		}

		// Check if META_EXPIRE is set and if it's expired
		if (isset($meta[self::META_EXPIRE]) && (int) $meta[self::META_EXPIRE] < time()) {
			$this->remove($meta[self::KEY]);

			return false;
		}

		// Check if META_CALLBACKS are valid
		if (
			isset($meta[self::META_CALLBACKS])
			&& $meta[self::META_CALLBACKS] !== []
			&& !Caching\Cache::checkCallbacks($meta[self::META_CALLBACKS])
		) {
			$this->remove($meta[self::KEY]);

			return false;
		}

		// Verify META_ITEMS recursively
		if (isset($meta[self::META_ITEMS]) && $meta[self::META_ITEMS] !== []) {
			foreach ($meta[self::META_ITEMS] as $itemKey => $time) {
				$m = $this->readMeta($itemKey);
				$metaTime = $m[self::META_TIME] ?? null;

				// If metaTime doesn't match or recursive verify fails, remove and return false
				if ($metaTime !== $time || ($m !== null && !$this->verify($m))) {
					$this->remove($meta[self::KEY]);

					return false;
				}
			}
		}

		// If all checks pass, return true
		return true;
	}

	/**
	 * @return array<mixed>|null
	 */
	private function readMeta(string $key): array|null
	{
		$stored = $this->doRead($key);

		if ($stored === null) {
			return null;
		}

		return $stored->getMeta();
	}

	private function doRead(string $key): Record|null
	{
		$stored = $this->client->get($this->formatEntryKey($key));

		if ($stored === null) {
			return null;
		}

		try {
			return $this->processStoredValue($key, $stored);
		} catch (JsonException) {
			return null;
		}
	}

	/**
	 * @param array<string> $keys
	 *
	 * @return array<string, Record|null>
	 */
	private function doMultiRead(array $keys): array
	{
		$formattedKeys = array_map([$this, 'formatEntryKey'], $keys);

		$result = [];

		foreach ($this->client->mGet($formattedKeys) as $index => $stored) {
			$key = $keys[$index];

			try {
				$result[$key] = $stored !== null ? $this->processStoredValue($key, $stored) : null;
			} catch (JsonException) {
				$result[$key] = null;
			}
		}

		return $result;
	}

	/**
	 * @throws JsonException
	 */
	private function processStoredValue(string $key, string $storedValue): Record
	{
		[$meta, $data] = explode(self::NS_SEPARATOR, $storedValue, 2) + [null, null];

		assert($data !== null);

		return new Record(
			$key,
			[...[self::KEY => $key], ...(array) Utils\Json::decode((string) $meta, true)],
			$data,
		);
	}

}
