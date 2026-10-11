<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Services;

use BadMethodCallException;
use Clue\React\Multicast;
use Nette;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

/**
 * React multicast server factory
 */
final readonly class MulticastFactory
{

	use Nette\SmartObject;

	public function __construct(private EventLoop\LoopInterface $eventLoop)
	{
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function create(string $address, int $port): Datagram\SocketInterface
	{
		return (new Multicast\Factory($this->eventLoop))->createReceiver($address . ':' . $port);
	}

}
