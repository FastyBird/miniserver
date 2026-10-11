<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar API factory
 */
interface OpenPulsarFactory
{

	public function create(
		string $identifier,
		string $accessId,
		string $accessSecret,
		Types\OpenPulsarTopic $topic,
		Types\OpenPulsarEndpoint $endpoint,
	): OpenPulsar;

}
