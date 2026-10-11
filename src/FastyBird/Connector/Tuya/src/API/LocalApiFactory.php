<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;

/**
 * Local device API factory
 */
interface LocalApiFactory
{

	/**
	 * @param array<ValueObjects\LocalChild> $children
	 */
	public function create(
		string $identifier,
		string|null $gateway,
		string|null $nodeId,
		string $localKey,
		string $ipAddress,
		Types\DeviceProtocolVersion $protocolVersion,
		array $children = [],
	): LocalApi;

}
