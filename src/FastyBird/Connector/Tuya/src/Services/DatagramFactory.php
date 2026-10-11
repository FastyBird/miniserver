<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Services;

use React\Datagram;
use React\EventLoop;
use React\Promise;

/**
 * React datagram server factory
 */
final readonly class DatagramFactory
{

	public function __construct(private EventLoop\LoopInterface $eventLoop)
	{
	}

	/**
	 * @return Promise\PromiseInterface<Datagram\Socket>
	 */
	public function create(string $address, int $port): Promise\PromiseInterface
	{
		return (new Datagram\Factory($this->eventLoop))->createServer($address . ':' . $port);
	}

}
