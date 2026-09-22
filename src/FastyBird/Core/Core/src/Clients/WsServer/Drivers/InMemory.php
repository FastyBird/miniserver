<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer\Drivers;

use FastyBird\Core\Entities\WsServer as Entities;
use Override;
use function array_values;

/**
 * Classic memory client storage driver
 */
final class InMemory implements IDriver
{

	private array $elements;

	public function __construct()
	{
		$this->elements = [];
	}

	#[Override]
	public function fetch(int $id): Entities\IClient|bool
	{
		if (!$this->contains($id)) {
			return false;
		}

		return $this->elements[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function fetchAll(): array
	{
		return array_values($this->elements);
	}

	#[Override]
	public function contains(int $id): bool
	{
		return isset($this->elements[$id]);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function save(int $id, $data, int $lifeTime = 0): bool
	{
		$this->elements[$id] = $data; // Lifetime is not supported

		return true;
	}

	#[Override]
	public function delete(int $id): bool
	{
		unset($this->elements[$id]);

		return true;
	}

}
