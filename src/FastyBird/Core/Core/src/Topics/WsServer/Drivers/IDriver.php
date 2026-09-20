<?php declare(strict_types = 1);

/**
 * IDriver.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 * @since          1.0.0
 *
 * @date           26.02.17
 */

namespace FastyBird\Core\Topics\WsServer\Drivers;

use FastyBird\Core\Entities\WsServer\Topics as Entities;

/**
 * Topics storage driver interface
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IDriver
{

	public function fetch(string $id): Entities\ITopic|bool;

	/**
	 * @return array<Entities\ITopic>
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
