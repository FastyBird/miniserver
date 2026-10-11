<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use Evenement\EventEmitter;
use FastyBird\Connector\HomeKit\Documents;
use Nette;
use Override;
use React\Socket;
use SplObjectStorage;
use function str_replace;

/**
 * HTTP secured server wrapper
 */
final class SecureServer extends EventEmitter implements Socket\ServerInterface
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<SecureConnection, null> */
	private SplObjectStorage $activeConnections;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Socket\ServerInterface $server,
		private readonly SecureConnectionFactory $secureConnectionFactory,
		private string|null $sharedKey = null,
	)
	{
		$this->activeConnections = new SplObjectStorage();

		$this->server->on('connection', function (Socket\ConnectionInterface $connection): void {
			$securedConnection = $this->secureConnectionFactory->create(
				$this->connector,
				$this->sharedKey,
				$connection,
			);

			$this->emit('connection', [$securedConnection]);

			$this->activeConnections->offsetSet($securedConnection);

			$securedConnection->on('close', function () use ($securedConnection): void {
				$this->activeConnections->offsetUnset($securedConnection);
			});
		});

		$this->server->on('error', function ($error): void {
			$this->emit('error', [$error]);
		});
	}

	public function setSharedKey(string|null $sharedKey): void
	{
		$this->sharedKey = $sharedKey;

		$this->activeConnections->rewind();

		foreach ($this->activeConnections as $connection) {
			$connection->setSharedKey($sharedKey);
		}
	}

	#[Override]
	public function getAddress(): string|null
	{
		$address = $this->server->getAddress();

		if ($address === null) {
			return null;
		}

		return str_replace('tcp://', 'tls://', $address);
	}

	#[Override]
	public function pause(): void
	{
		$this->server->pause();
	}

	#[Override]
	public function resume(): void
	{
		$this->server->resume();
	}

	#[Override]
	public function close(): void
	{
		$this->server->close();
	}

}
