<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\Security\Identity;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Handshake;
use React\Socket;

/**
 * Single client connection interface
 */
interface ConnectedClient
{

	public function getId(): int;

	public function getConnection(): Socket\ConnectionInterface;

	public function setHttpHeadersReceived(bool $state): void;

	public function isHttpHeadersReceived(): bool;

	public function setHttpBuffer(string $buffer): void;

	public function getHttpBuffer(): string;

	public function setRequest(Handshake\Request $httpRequest): void;

	public function getRequest(): Handshake\Request;

	public function setWebSocket(WebSocket $webSocket): void;

	public function getWebSocket(): WebSocket;

	public function addParameter(string $key, mixed $value): void;

	public function getParameter(string $key, mixed $default = null): mixed;

	public function close(int|null $code = null): void;

	public function send(Responses\ControllerResponse|Encoding\FrameData|string $response): void;

	/**
	 * Keeps the identity the client's access token resolved to at its latest check, with the
	 * role names the HTTP API would check for it. A failed check stores null, and no roles.
	 *
	 * @param array<string> $roles
	 */
	public function setIdentity(Identity\UserIdentity|null $identity, array $roles = []): void;

	public function getIdentity(): Identity\UserIdentity|null;

	/**
	 * @return array<string>
	 */
	public function getRoles(): array;

}
