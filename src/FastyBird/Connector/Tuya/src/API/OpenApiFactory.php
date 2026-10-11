<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;

/**
 * OpenAPI API factory
 */
interface OpenApiFactory
{

	public function create(
		string $identifier,
		string $accessId,
		string $accessSecret,
		Types\OpenApiEndpoint $endpoint,
		string $lang = 'en',
	): OpenApi;

}
