<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Topics;

use FastyBird\Core\WebSockets\Entities\Topics;
use IteratorAggregate;

/**
 * Storage for manage all topics
 */
interface IStorage extends IteratorAggregate
{

	public function setStorageDriver(Drivers\IDriver $driver): void;

	public static function getStorageId(Topics\ITopic $topic): string;

	public function getTopic(string $identifier): Topics\ITopic;

	public function addTopic(string $identifier, Topics\ITopic $topic): void;

	public function hasTopic(string $identifier): bool;

	public function removeTopic(string $identifier): bool;

}
