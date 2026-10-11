<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\API;

/**
 * Base client factory
 */
interface ClientFactory
{

	public function create(
		string $clientId,
		string $address,
		int $port,
		string|null $username = null,
		string|null $password = null,
	): Client;

}
