<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Services;

use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Socket;

/**
 * Socket client factory
 */
final class SocketClientFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function create(): Socket\Connector
	{
		return new Socket\Connector(
			[
				'dns' => '8.8.8.8',
				'timeout' => 10,
				'tls' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'check_hostname' => false,
					'allow_self_signed' => true,
				],
			],
			$this->eventLoop,
		);
	}

}
