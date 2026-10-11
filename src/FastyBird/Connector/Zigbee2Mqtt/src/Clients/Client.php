<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use React\Promise;

/**
 * Base device client interface
 */
interface Client
{

	/**
	 * Create client
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function connect(): Promise\PromiseInterface;

	/**
	 * Destroy client
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function disconnect(): Promise\PromiseInterface;

}
