<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Clients;

/**
 * Base device client interface
 */
interface Client
{

	/**
	 * Create client
	 */
	public function connect(): void;

	/**
	 * Destroy client
	 */
	public function disconnect(): void;

}
