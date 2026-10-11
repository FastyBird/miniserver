<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

use Ramsey\Uuid;

/**
 * Generation 2 device websockets API factory
 */
interface Gen2WsApiFactory
{

	public function create(
		Uuid\UuidInterface $id,
		string|null $ipAddress,
		string|null $domain,
		string|null $username,
		string|null $password,
	): Gen2WsApi;

}
