<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Services;

use Clue\React\Multicast;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

/**
 * React multicast server factory
 */
final readonly class MulticastFactory
{

	public function __construct(private EventLoop\LoopInterface $eventLoop)
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
