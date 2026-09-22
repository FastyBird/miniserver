<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http;
use Throwable;

/**
 * WebSockets application interface
 */
interface IApplication
{

	/**
	 * When a new connection is opened it will be passed to this method
	 */
	public function handleOpen(Entities\IClient $client, Http\IRequest $httpRequest): void;

	/**
	 * This is called before or after a socket is closed (depends on how it's closed)
	 * SendMessage to $client will not result in an error if it has already been closed
	 */
	public function handleClose(Entities\IClient $client, Http\IRequest $httpRequest): void;

	/**
	 * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
	 * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
	 */
	public function handleError(Entities\IClient $client, Http\IRequest $httpRequest, Throwable $ex): void;

	/**
	 * Triggered when a client sends data through the socket
	 */
	public function handleMessage(Entities\IClient $from, Http\IRequest $httpRequest, string $message): void;

	/**
	 * @todo This method may be removed in future version (note that will not break code, just make some code obsolete)
	 * If any component in a stack supports a WebSocket sub-protocol return each supported in an array
	 */
	public function getSubProtocols(): array;

}
