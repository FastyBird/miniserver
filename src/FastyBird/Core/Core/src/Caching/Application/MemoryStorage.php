<?php declare(strict_types = 1);

namespace FastyBird\Core\Caching\Application;

use Nette;
use Nette\Caching;
use Override;
use function array_key_exists;
use function in_array;
use function is_array;

final class MemoryStorage implements Caching\Storage
{

	use Nette\SmartObject;

	private const string DATA_KEY = 'data';

	private const string DEPENDENCIES_KEY = 'dependencies';

	/** @var array<string, array<string, mixed|array<mixed>>> */
	private array $data = [];

	#[Override]
	public function read(string $key): mixed
	{
		return $this->data[$key][self::DATA_KEY] ?? null;
	}

	#[Override]
	public function lock(string $key): void
	{
		// Lock is not implemented
	}

	/**
	 * @param array<mixed> $dependencies
	 */
	#[Override]
	public function write(string $key, mixed $data, array $dependencies = []): void
	{
		$this->data[$key] = [
			self::DATA_KEY => $data,
			self::DEPENDENCIES_KEY => $dependencies,
		];
	}

	#[Override]
	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}

	/**
	 * @param array<mixed> $conditions
	 */
	#[Override]
	public function clean(array $conditions): void
	{
		if (array_key_exists(Caching\Cache::All, $conditions)) {
			$this->data = [];
		} elseif (
			array_key_exists(Caching\Cache::Tags, $conditions)
			&& is_array($conditions[Caching\Cache::Tags])
		) {
			foreach ($conditions[Caching\Cache::Tags] as $tag) {
				foreach ($this->data as $key => $cached) {
					if (
						array_key_exists(self::DEPENDENCIES_KEY, $cached)
						&& is_array($cached[self::DEPENDENCIES_KEY])
						&& array_key_exists(Caching\Cache::Tags, $cached[self::DEPENDENCIES_KEY])
						&& is_array($cached[self::DEPENDENCIES_KEY][Caching\Cache::Tags])
						&& in_array($tag, $cached[self::DEPENDENCIES_KEY][Caching\Cache::Tags], true)
					) {
						unset($this->data[$key]);
					}
				}
			}
		}
	}

}
