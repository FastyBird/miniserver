<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http\WebSockets as Http;

/**
 * A standard interface for interacting with the various version of the WebSocket protocol
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Protocols
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IProtocol
{

	/**
	 * Although the version has a name associated with it the integer returned is the proper identification
	 */
	public function getVersion(): int;

	/**
	 * Given an HTTP header, determine if this version should handle the protocol
	 */
	public function isVersion(Http\IRequest $httpRequest): bool;

	/**
	 * Perform the handshake and return the response headers
	 */
	public function doHandshake(Http\IRequest $httpRequest): Http\IResponse;

	public function handleMessage(
		Entities\IClient $client,
		Application\IApplication $application,
		string $message = '',
	): void;

	public function send(Entities\IClient $client, string|int|IData $payload): void;

	public function close(Entities\IClient $client, int|null $code = null): void;

}
