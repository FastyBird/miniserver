<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Clients;

use FastyBird\Core\WebSockets\Entities;
use IteratorAggregate;

/**
 * Storage for manage all connections
 */
interface IStorage extends IteratorAggregate
{

	public function setStorageDriver(Drivers\IDriver $driver): void;

	public function getClient(int $identifier): Entities\ConnectedClient;

	public function addClient(int $identifier, Entities\ConnectedClient $client): void;

	public function hasClient(int $identifier): bool;

	public function removeClient(int $identifier): bool;

	public function refreshClient(Entities\ConnectedClient $client): void;

}
