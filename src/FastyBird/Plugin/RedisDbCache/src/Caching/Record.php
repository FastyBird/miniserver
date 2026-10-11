<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Caching;

/**
 * Cache record item
 */
final readonly class Record
{

	/**
	 * @param array<mixed> $meta
	 */
	public function __construct(
		private string $key,
		private array $meta,
		private string $data,
	)
	{
	}

	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * @return array<mixed>
	 */
	public function getMeta(): array
	{
		return $this->meta;
	}

	public function getData(): string
	{
		return $this->data;
	}

}
