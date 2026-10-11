<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API;

use FastyBird\Connector\Sonoff\Types;

/**
 * Cloud API factory
 */
interface CloudApiFactory
{

	public function create(
		string $username,
		string $password,
		string $appId,
		string $appSecret,
		Types\Region|null $region = null,
	): CloudApi;

}
