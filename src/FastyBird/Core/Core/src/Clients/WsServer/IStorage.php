<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use IteratorAggregate;

/**
 * Storage for manage all connections
 */
interface IStorage extends IteratorAggregate
{

	public function setStorageDriver(Drivers\IDriver $driver): void;

	public function getClient(int $identifier): Entities\IClient;

	public function addClient(int $identifier, Entities\IClient $client): void;

	public function hasClient(int $identifier): bool;

	public function removeClient(int $identifier): bool;

	public function refreshClient(Entities\IClient $client): void;

}
