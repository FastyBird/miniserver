<?php declare(strict_types = 1);

namespace FastyBird\Core\Topics\WsServer;

use FastyBird\Core\Entities\WsServer\Topics as Entities;
use IteratorAggregate;

/**
 * Storage for manage all topics
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
