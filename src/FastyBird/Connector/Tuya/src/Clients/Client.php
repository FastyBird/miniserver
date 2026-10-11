<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Clients;

/**
 * Base client service
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
