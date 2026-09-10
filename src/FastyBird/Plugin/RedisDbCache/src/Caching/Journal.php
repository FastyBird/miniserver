<?php declare(strict_types = 1);

/**
 * Journal.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           15.08.24
 */

namespace FastyBird\Plugin\RedisDbCache\Caching;

use FastyBird\Plugin\RedisDbCache\Clients;
use Nette\Caching;
use function array_merge;
use function array_unique;
use function assert;
use function is_array;
use function is_string;

/**
 * Redis cache journal
 *
 * @package        FastyBird:RedisDbCachePlugin!
 * @subpackage     Caching
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Journal implements Caching\Storages\Journal
{

	public const NS_PREFIX = 'FB.Journal';

	public const KEY_PRIORITY = 'priority';

	public const SUFFIX_TAGS = 'tags';

	public const SUFFIX_KEYS = 'keys';

	public function __construct(private readonly Clients\Client $client)
	{
	}

	/**
	 * Writes entry information into the journal.
	 *
	 * @param array<mixed> $dependencies
	 */
	public function write(string $key, array $dependencies): void
	{
		$this->cleanEntry($key);

		$this->client->multi();

		// Add entry to each tag & tag to entry
		if (isset($dependencies[Caching\Cache::Tags])) {
			foreach (array_unique((array) $dependencies[Caching\Cache::Tags]) as $tag) {
				assert(is_string($tag));

				$this->client->sAdd($this->formatKey($tag, self::SUFFIX_KEYS), [$key]);
				$this->client->sAdd($this->formatKey($key, self::SUFFIX_TAGS), [$tag]);
			}
		}

		if (isset($dependencies[Caching\Cache::Priority])) {
			$this->client->zAdd($this->formatKey(self::KEY_PRIORITY), [$key => $dependencies[Caching\Cache::Priority]]);
		}

		$this->client->exec();
	}

	/**
	 * Cleans entries from journal
	 * Return array of removed items or NULL when performing a full cleanup
	 *
	 * @param array<mixed> $conditions
	 *
	 * @return array<mixed>|null
	 */
	// @phpstan-ignore method.childReturnType (Nette\Caching\Storages\Journal::clean() signature changed upstream; keeping the wider return type is a pre-existing, deliberate deviation)
	public function clean(array $conditions): array|null
	{
		if (isset($conditions[Caching\Cache::All])) {
			$all = $this->client->keys(self::NS_PREFIX . ':*');

			$this->client->del($all);

			return null;
		}

		$entries = [];

		if (isset($conditions[Caching\Cache::Tags])) {
			foreach ((array) $conditions[Caching\Cache::Tags] as $tag) {
				assert(is_string($tag));

				$this->cleanEntry($found = $this->tagEntries($tag));

				$entries[] = $found;
			}

			$entries = array_merge(...$entries);
		}

		if (isset($conditions[Caching\Cache::Priority])) {
			$this->cleanEntry($found = $this->priorityEntries($conditions[Caching\Cache::Priority]));

			$entries = array_merge($entries, $found);
		}

		return array_unique($entries);
	}

	/**
	 * Deletes all keys from associated tags and all priorities
	 *
	 * @param array<string>|string $keys
	 */
	public function cleanEntry(array|string $keys): void
	{
		foreach (is_array($keys) ? $keys : [$keys] as $key) {
			$entries = $this->entryTags($key);

			$this->client->multi();

			foreach ($entries as $tag) {
				$this->client->sRem($this->formatKey($tag, self::SUFFIX_KEYS), $key);
			}

			// Drop tags of entry and priority, in case there are some
			$this->client->del($this->formatKey($key, self::SUFFIX_TAGS));
			$this->client->zRem($this->formatKey(self::KEY_PRIORITY), $key);

			$this->client->exec();
		}
	}

	/**
	 * @return array<string>
	 */
	private function priorityEntries(int $priority): array
	{
		return $this->client->zRangeByScore($this->formatKey(self::KEY_PRIORITY), 0, $priority);
	}

	/**
	 * @return array<string>
	 */
	private function entryTags(string $key): array
	{
		return $this->client->sMembers($this->formatKey($key, self::SUFFIX_TAGS));
	}

	/**
	 * @return array<string>
	 */
	private function tagEntries(string $tag): array
	{
		return $this->client->sMembers($this->formatKey($tag, self::SUFFIX_KEYS));
	}

	private function formatKey(string $key, string|null $suffix = null): string
	{
		return self::NS_PREFIX . ':' . $key . ($suffix !== null ? ':' . $suffix : null);
	}

}
