<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Topics\Drivers;

use FastyBird\Core\WebSockets\Entities\Topics;

/**
 * Topics storage driver interface
 */
interface IDriver
{

	public function fetch(string $id): Topics\ITopic|bool;

	/**
	 * @return array<Topics\ITopic>
	 */
	public function fetchAll(): array;

	public function contains(string $id): bool;

	/**
	 * @return bool True if saved, false otherwise
	 */
	public function save(string $id, mixed $data, int $lifeTime = 0): bool;

	/**
	 * @return bool true if the cache entry was successfully deleted, false otherwise
	 */
	public function delete(string $id): bool;

}
