<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Handshake;
use Nette\Security as NS;
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

	public function setRequest(Handshake\IRequest $httpRequest): void;

	public function getRequest(): Handshake\IRequest;

	public function setWebSocket(IWebSocket $webSocket): void;

	public function getWebSocket(): IWebSocket;

	public function addParameter(string $key, mixed $value): void;

	public function getParameter(string $key, mixed $default = null): mixed;

	public function close(int|null $code = null): void;

	public function send(Responses\ControllerResponse|Encoding\FrameData|string $response): void;

	public function setUser(NS\User $user): void;

	public function getUser(): NS\User|null;

}
