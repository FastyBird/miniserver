<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use IteratorAggregate;

/**
 * Storage for manage all connections
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
