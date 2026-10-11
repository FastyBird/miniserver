<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Clients;

/**
 * Connector client interface
 */
interface Client
{

	/**
	 * Create clients
	 */
	public function connect(): void;

	/**
	 * Destroy clients
	 */
	public function disconnect(): void;

}
