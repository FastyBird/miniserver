<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\API;

/**
 * Television API factory
 */
interface TelevisionApiFactory
{

	public function create(
		string $identifier,
		string $ipAddress,
		int $port,
		string|null $appId = null,
		string|null $encryptionKey = null,
		string|null $macAddress = null,
	): TelevisionApi;

}
