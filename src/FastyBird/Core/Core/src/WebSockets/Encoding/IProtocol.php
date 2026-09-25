<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;

/**
 * A standard interface for interacting with the various version of the WebSocket protocol
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
	public function isVersion(Handshake\IRequest $httpRequest): bool;

	/**
	 * Perform the handshake and return the response headers
	 */
	public function doHandshake(Handshake\IRequest $httpRequest): Handshake\IResponse;

	public function handleMessage(
		Entities\ConnectedClient $client,
		Controllers\Dispatcher $application,
		string $message = '',
	): void;

	public function send(Entities\ConnectedClient $client, string|int|FrameData $payload): void;

	public function close(Entities\ConnectedClient $client, int|null $code = null): void;

}
