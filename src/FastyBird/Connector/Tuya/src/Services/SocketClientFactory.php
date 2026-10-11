<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Services;

use InvalidArgumentException;
use React\EventLoop;
use React\Socket;

/**
 * Socket client factory
 */
final readonly class SocketClientFactory
{

	public function __construct(private EventLoop\LoopInterface $eventLoop)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function create(): Socket\Connector
	{
		return new Socket\Connector($this->eventLoop);
	}

}
