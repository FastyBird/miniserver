<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Servers;

/**
 * Connector base communication server
 */
interface Server
{

	/**
	 * Create server
	 */
	public function connect(): void;

	/**
	 * Destroy server
	 */
	public function disconnect(): void;

}
