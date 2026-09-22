<?php declare(strict_types = 1);

namespace FastyBird\Core\Caching\Application;

use Nette\Caching;
use Override;

final class MemoryAdapterStorage implements Caching\Storage
{

	private MemoryStorage $memoryStorage;

	public function __construct(private readonly Caching\Storage $cachedStorage)
	{
		$this->memoryStorage = new MemoryStorage();
	}

	/**
	 * Read from cache
	 */
	#[Override]
	public function read(string $key): mixed
	{
		// Get data from memory storage
		$data = $this->memoryStorage->read($key);

		if ($data !== null) {
			return $data;
		}

		// Get data from cached storage and write them to memory storage
		$data = $this->cachedStorage->read($key);

		if ($data !== null) {
			$this->memoryStorage->write($key, $data, []);
		}

		return $data;
	}

	/**
	 * Prevents item reading and writing
	 * Lock is released by write() or remove()
	 *
	 * Not implemented by MemoryStorage
	 */
	#[Override]
	public function lock(string $key): void
	{
		$this->cachedStorage->lock($key);
	}

	/**
	 * Writes item into the cache
	 *
	 * @param array<mixed> $dependencies
	 */
	#[Override]
	public function write(string $key, mixed $data, array $dependencies): void
	{
		$this->cachedStorage->write($key, $data, $dependencies);
		$this->memoryStorage->write($key, $data, $dependencies);
	}

	/**
	 * Removes item from the cache
	 */
	#[Override]
	public function remove(string $key): void
	{
		$this->cachedStorage->remove($key);
		$this->memoryStorage->remove($key);
	}

	/**
	 * Removes items from the cache by conditions
	 *
	 * @param array<mixed> $conditions
	 */
	#[Override]
	public function clean(array $conditions): void
	{
		$this->cachedStorage->clean($conditions);
		$this->memoryStorage->clean($conditions);
	}

}
