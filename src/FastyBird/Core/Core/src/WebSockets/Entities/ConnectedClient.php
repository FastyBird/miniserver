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

	/**
	 * Whether the client's HTTP handshake request has been read completely
	 */
	// phpcs:ignore Internal.ParseError.InterfaceHasMemberVar, Generic.Formatting.DisallowMultipleStatements.SameLine -- PHP_CodeSniffer 3 does not tokenize property hooks
	public bool $httpHeadersReceived { get; set; }

	/**
	 * The part of the HTTP handshake request received so far
	 */
	// phpcs:ignore Internal.ParseError.InterfaceHasMemberVar, Generic.Formatting.DisallowMultipleStatements.SameLine -- PHP_CodeSniffer 3 does not tokenize property hooks
	public string $httpBuffer { get; set; }

	public function getId(): int;

	public function getConnection(): Socket\ConnectionInterface;

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
