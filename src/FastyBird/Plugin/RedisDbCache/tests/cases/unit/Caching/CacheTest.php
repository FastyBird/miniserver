<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Tests\Cases\Unit\Caching;

use FastyBird\Plugin\RedisDbCache\Caching as RedisDbCacheCaching;
use FastyBird\Plugin\RedisDbCache\Clients;
use FastyBird\Plugin\RedisDbCache\Connections;
use FastyBird\Plugin\RedisDbCache\Exceptions;
use FastyBird\Plugin\RedisDbCache\Tests;
use Nette\Caching as NetteCaching;
use stdClass;
use function sleep;
use function sprintf;

final class CacheTest extends Tests\Cases\Unit\BaseTestCase
{

	private Clients\Client $client;

	private NetteCaching\Cache $cache;

	private RedisDbCacheCaching\Storage $storage;

	protected function setUp(): void
	{
		parent::setUp();

		$this->client = new Clients\Client(new Connections\Configuration());

		$journal = new RedisDbCacheCaching\Journal($this->client);

		$this->storage = new RedisDbCacheCaching\Storage($this->client, $journal);

		$this->cache = new NetteCaching\Cache($this->storage);
	}

	public function testBasic(): void
	{
		$this->cache->save('foo', 'bar');
		self::assertSame('bar', $this->cache->load('foo'));

		$this->cache->remove('foo');
		self::assertNull($this->cache->load('foo'));
	}

	public function testComplex(): void
	{
		$data = [
			'foo1' => 'bar',
			'foo2' => new stdClass(),
			'foo3' => ['bar' => 'baz'],
		];

		$this->cache->save('complex', $data, [NetteCaching\Cache::Expire => 6_000]);
		self::assertEquals($data, $this->cache->load('complex'));

		$this->cache->remove('complex');
		self::assertNull($this->cache->load('complex'));
	}

	public function testClean(): void
	{
		$this->cache->save('foo', 'bar', [NetteCaching\Cache::Tags => ['tag/tag', 'tag/foo']]);
		self::assertSame('bar', $this->cache->load('foo'));

		$this->cache->clean([NetteCaching\Cache::Tags => ['tag/foo']]);
		self::assertNull($this->cache->load('foo'));
	}

	public function testCleanMultiple(): void
	{
		$this->cache->save('foo1', 'bar1', [NetteCaching\Cache::Tags => ['tag']]);
		$this->cache->save('foo2', 'bar2', [NetteCaching\Cache::Tags => ['tag']]);
		self::assertSame('bar1', $this->cache->load('foo1'));
		self::assertSame('bar2', $this->cache->load('foo2'));

		$this->cache->clean([NetteCaching\Cache::Tags => ['tag']]);
		self::assertNull($this->cache->load('foo1'));
		self::assertNull($this->cache->load('foo2'));
	}

	public function testCleanAll(): void
	{
		$this->cache->save('foo1', 'bar1', [NetteCaching\Cache::Tags => ['tag']]);
		$this->cache->save('foo2', 'bar2', [NetteCaching\Cache::Tags => ['tag']]);
		$this->cache->save('foo3', 'bar3');
		self::assertSame('bar1', $this->cache->load('foo1'));
		self::assertSame('bar2', $this->cache->load('foo2'));
		self::assertSame('bar3', $this->cache->load('foo3'));

		$this->cache->clean([NetteCaching\Cache::All => true]);
		self::assertNull($this->cache->load('foo1'));
		self::assertNull($this->cache->load('foo2'));
		self::assertNull($this->cache->load('foo3'));
	}

	public function testOverride(): void
	{
		$this->cache->save('foo', 'bar');
		self::assertSame('bar', $this->cache->load('foo'));
		$this->cache->save('foo', 'bar2');
		self::assertSame('bar2', $this->cache->load('foo'));
	}

	public function testExpiration(): void
	{
		$this->cache->save('foo', 'bar', [NetteCaching\Cache::Expire => 1]);
		self::assertSame('bar', $this->cache->load('foo'));
		sleep(2);
		self::assertNull($this->cache->load('foo'));
	}

	public function testPriority(): void
	{
		$this->cache->save('foo1', 'bar1', [NetteCaching\Cache::Priority => 40]);
		$this->cache->save('foo2', 'bar2', [NetteCaching\Cache::Priority => 30]);
		$this->cache->save('foo3', 'bar3', [NetteCaching\Cache::Priority => 20]);
		$this->cache->save('foo4', 'bar4', [NetteCaching\Cache::Priority => 10]);
		self::assertSame('bar1', $this->cache->load('foo1'));
		self::assertSame('bar2', $this->cache->load('foo2'));
		self::assertSame('bar3', $this->cache->load('foo3'));
		self::assertSame('bar4', $this->cache->load('foo4'));

		$this->cache->clean([NetteCaching\Cache::Priority => 10]);
		self::assertSame('bar1', $this->cache->load('foo1'));
		self::assertSame('bar2', $this->cache->load('foo2'));
		self::assertSame('bar3', $this->cache->load('foo3'));
		self::assertNull($this->cache->load('foo4'));

		$this->cache->clean([NetteCaching\Cache::Priority => 30]);
		self::assertSame('bar1', $this->cache->load('foo1'));
		self::assertNull($this->cache->load('foo2'));
		self::assertNull($this->cache->load('foo3'));
		self::assertNull($this->cache->load('foo4'));

		$this->cache->clean([NetteCaching\Cache::Priority => 100]);
		self::assertNull($this->cache->load('foo1'));
		self::assertNull($this->cache->load('foo2'));
		self::assertNull($this->cache->load('foo3'));
		self::assertNull($this->cache->load('foo4'));
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testTagsCleaning(): void
	{
		$this->storage->clean([NetteCaching\Cache::All => true]);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->write('foo', 'bar', [NetteCaching\Cache::Tags => ['tag']]);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->clean([NetteCaching\Cache::Tags => ['tag']]);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->write('foo', 'bar', [NetteCaching\Cache::Tags => ['tag']]);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->remove('foo');
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->write('foo', 'bar', [NetteCaching\Cache::Tags => ['tag'], NetteCaching\Cache::Priority => 1]);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertTrue(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);

		$this->storage->clean([NetteCaching\Cache::Priority => 1]);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('foo', RedisDbCacheCaching\Journal::SUFFIX_TAGS, true)),
		);
		self::assertFalse(
			$this->client->exists($this->generateJournalKey('tag', RedisDbCacheCaching\Journal::SUFFIX_KEYS, false)),
		);
	}

	private function generateJournalKey(string $key, string $suffix, bool $addStoragePrefix): string
	{
		$prefix = $addStoragePrefix
			? sprintf(
				'%s:%s',
				RedisDbCacheCaching\Journal::NS_PREFIX,
				RedisDbCacheCaching\Storage::NS_PREFIX,
			)
			: RedisDbCacheCaching\Journal::NS_PREFIX;

		return sprintf('%s:%s:%s', $prefix, $key, $suffix);
	}

}
