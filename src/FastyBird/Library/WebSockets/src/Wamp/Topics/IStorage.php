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

namespace FastyBird\Library\WebSockets\Wamp\Topics;

use FastyBird\Library\WebSockets\Wamp\Entities;
use FastyBird\Library\WebSockets\Wamp\Topics;
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

	public function setStorageDriver(Topics\Drivers\IDriver $driver): void;

	public static function getStorageId(Entities\Topics\ITopic $topic): string;

	public function getTopic(string $identifier): Entities\Topics\ITopic;

	public function addTopic(string $identifier, Entities\Topics\ITopic $topic): void;

	public function hasTopic(string $identifier): bool;

	public function removeTopic(string $identifier): bool;

}
