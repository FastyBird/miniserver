<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\API;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use Nette;
use function array_key_exists;
use function md5;

/**
 * Client connections manager
 */
final class ConnectionManager
{

	use Nette\SmartObject;

	/** @var array<string, Client> */
	private array $clientConnections = [];

	public function __construct(private readonly ClientFactory $clientFactory)
	{
	}

	public function getClient(
		string $clientId,
		string $serverAddress = Entities\Connectors\Connector::DEFAULT_SERVER_ADDRESS,
		int $serverPort = Entities\Connectors\Connector::DEFAULT_SERVER_PORT,
		string|null $username = null,
		string|null $password = null,
	): Client
	{
		$hash = md5($clientId . $serverAddress . $serverPort . $username . $password);

		if (!array_key_exists($hash, $this->clientConnections)) {
			$this->clientConnections[$hash] = $this->clientFactory->create(
				$clientId,
				$serverAddress,
				$serverPort,
				$username,
				$password,
			);
		}

		return $this->clientConnections[$hash];
	}

	public function __destruct()
	{
		foreach ($this->clientConnections as $connection) {
			if ($connection->isConnected()) {
				$connection->disconnect();
			}
		}
	}

}
