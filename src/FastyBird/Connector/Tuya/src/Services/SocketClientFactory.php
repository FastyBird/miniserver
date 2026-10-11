<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Services;

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
		return new Socket\Connector($this->eventLoop);
	}

}
