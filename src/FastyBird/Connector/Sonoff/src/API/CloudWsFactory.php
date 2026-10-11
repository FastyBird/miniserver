<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API;

use FastyBird\Connector\Sonoff\Types;

/**
 * Cloud WS factory
 */
interface CloudWsFactory
{

	public function create(
		string $accessToken,
		string $appId,
		string $apiKey,
		Types\Region $region,
	): CloudWs;

}
