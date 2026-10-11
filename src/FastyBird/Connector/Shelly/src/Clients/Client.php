<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

/**
 * Base device client interface
 */
interface Client
{

	/**
	 * Create servers/clients
	 */
	public function connect(): void;

	/**
	 * Destroy servers/clients
	 */
	public function disconnect(): void;

}
