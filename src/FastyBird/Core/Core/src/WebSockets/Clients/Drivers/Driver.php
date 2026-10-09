<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Clients\Drivers;

use FastyBird\Core\WebSockets\Entities;

/**
 * Clients storage driver interface
 */
interface Driver
{

	public function fetch(int $id): Entities\Client|bool;

	/**
	 * @return array<Entities\Client>
	 */
	public function fetchAll(): array;

	public function contains(int $id): bool;

	/**
	 * @return bool True if saved, false otherwise
	 */
	public function save(int $id, mixed $data, int $lifeTime = 0): bool;

	/**
	 * @return bool true if the cache entry was successfully deleted, false otherwise
	 */
	public function delete(int $id): bool;

}
