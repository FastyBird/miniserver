<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer;

use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Encoding\WebSockets as Protocols;
use FastyBird\Core\Entities\WebSockets;
use FastyBird\Core\Http;
use Nette\Security as NS;
use React\Socket;

/**
 * Single client connection interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IClient
{

	public function getId(): int;

	public function getConnection(): Socket\ConnectionInterface;

	public function setHttpHeadersReceived(bool $state): void;

	public function isHttpHeadersReceived(): bool;

	public function setHttpBuffer(string $buffer): void;

	public function getHttpBuffer(): string;

	public function setRequest(Http\IRequest $httpRequest): void;

	public function getRequest(): Http\IRequest;

	public function setWebSocket(WebSockets\IWebSocket $webSocket): void;

	public function getWebSocket(): WebSockets\IWebSocket;

	public function addParameter(string $key, mixed $value): void;

	public function getParameter(string $key, mixed $default = null): mixed;

	public function close(int|null $code = null): void;

	public function send(Responses\IResponse|Protocols\IData|string $response): void;

	public function setUser(NS\User $user): void;

	public function getUser(): NS\User|null;

}
