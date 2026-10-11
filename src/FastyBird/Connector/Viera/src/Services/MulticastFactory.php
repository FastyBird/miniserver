<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Services;

use Clue\React\Multicast;
use Nette;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

/**
 * React multicast server factory
 */
final class MulticastFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws RuntimeException
	 */
	public function create(): Datagram\SocketInterface
	{
		return (new Multicast\Factory($this->eventLoop))->createSender();
	}

}
