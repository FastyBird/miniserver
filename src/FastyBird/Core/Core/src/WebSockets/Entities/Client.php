<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Handshake;
use Nette\Utils;
use React\Socket;

/**
 * Single client connection
 */
class Client
{

	private Identity\UserIdentity|null $identity = null;

	/** @var array<string> */
	private array $roles = [];

	/**
	 * Whether the client's HTTP handshake request has been read completely
	 */
	public bool $httpHeadersReceived = false;

	/**
	 * The part of the HTTP handshake request received so far
	 */
	public string $httpBuffer = '';

	private string|null $remoteAddress = null;

	private Handshake\Request $httpRequest;

	private WebSocket $webSocket;

	private Utils\ArrayHash $parameters;

	public function __construct(private int $id, private Socket\ConnectionInterface $connection)
	{
		$this->remoteAddress = $connection->getRemoteAddress();

		$this->parameters = new Utils\ArrayHash();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getConnection(): Socket\ConnectionInterface
	{
		return $this->connection;
	}

	public function setRequest(Handshake\Request $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}

	public function getRequest(): Handshake\Request
	{
		return clone $this->httpRequest;
	}

	public function setWebSocket(WebSocket $webSocket): void
	{
		$this->webSocket = $webSocket;
	}

	public function getWebSocket(): WebSocket
	{
		if ($this->webSocket === null) {
			throw new Exceptions\InvalidState('Socket is not defined');
		}

		return $this->webSocket;
	}

	public function addParameter(string $key, $value): void
	{
		$this->parameters->offsetSet($key, $value);
	}

	public function getParameter(string $key, mixed $default = null): mixed
	{
		return $this->parameters->offsetExists($key) ? $this->parameters->offsetGet($key) : $default;
	}

	public function close(int|null $code = null): void
	{
		$this->webSocket->getProtocol()->close($this, $code);
	}

	public function send($response): void
	{
		if ($response instanceof Responses\ControllerResponse) {
			$response = (string) $response;
		}

		$this->webSocket->getProtocol()->send($this, $response);
	}

	/**
	 * Keeps the identity the client's access token resolved to at its latest check, with the
	 * role names the HTTP API would check for it. A failed check stores null, and no roles.
	 *
	 * @param array<string> $roles
	 */
	public function setIdentity(Identity\UserIdentity|null $identity, array $roles = []): void
	{
		$this->identity = $identity;
		$this->roles = $identity !== null ? $roles : [];
	}

	public function getIdentity(): Identity\UserIdentity|null
	{
		return $this->identity;
	}

	/**
	 * @return array<string>
	 */
	public function getRoles(): array
	{
		return $this->roles;
	}

}
