<?php declare(strict_types = 1);

/**
 * IStorage.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 * @since          1.0.0
 *
 * @date           24.02.17
 */

namespace FastyBird\Core\Topics\WsServer;

use FastyBird\Core\Entities\WsServer\Topics as Entities;
use IteratorAggregate;

/**
 * Storage for manage all topics
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IStorage extends IteratorAggregate
{

	public function setStorageDriver(Drivers\IDriver $driver): void;

	public static function getStorageId(Entities\ITopic $topic): string;

	public function getTopic(string $identifier): Entities\ITopic;

	public function addTopic(string $identifier, Entities\ITopic $topic): void;

	public function hasTopic(string $identifier): bool;

	public function removeTopic(string $identifier): bool;

}
