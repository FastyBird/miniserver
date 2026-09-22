<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer\Drivers;

use FastyBird\Core\Entities\WsServer as Entities;

/**
 * Clients storage driver interface
 */
interface IDriver
{

	public function fetch(int $id): Entities\IClient|bool;

	/**
	 * @return array<Entities\IClient>
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
