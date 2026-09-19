<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Clients\Drivers;

use FastyBird\Library\WebSockets\Entities;

/**
 * Clients storage driver interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IDriver
{

	public function fetch(int $id): Entities\Clients\IClient|bool;

	/**
	 * @return array<Entities\Clients\IClient>
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
