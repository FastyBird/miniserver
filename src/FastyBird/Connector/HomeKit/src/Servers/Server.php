<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

/**
 * HomeKit device server interface
 */
interface Server
{

	/**
	 * Initialize server
	 */
	public function initialize(): void;

	/**
	 * Create server
	 */
	public function connect(): void;

	/**
	 * Destroy server
	 */
	public function disconnect(): void;

}
