<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Handshake;
use Nette\Utils;
use Override;
use React\Socket;

/**
 * Single client connection
 */
class Client implements ConnectedClient
{

	private Identity\UserIdentity|null $identity = null;

	/** @var array<string> */
	private array $roles = [];

	public bool $httpHeadersReceived = false;

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

	#[Override]
	public function getId(): int
	{
		return $this->id;
	}

	#[Override]
	public function getConnection(): Socket\ConnectionInterface
	{
		return $this->connection;
	}

	#[Override]
	public function setRequest(Handshake\Request $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}

	#[Override]
	public function getRequest(): Handshake\Request
	{
		return clone $this->httpRequest;
	}

	#[Override]
	public function setWebSocket(WebSocket $webSocket): void
	{
		$this->webSocket = $webSocket;
	}

	#[Override]
	public function getWebSocket(): WebSocket
	{
		if ($this->webSocket === null) {
			throw new Exceptions\InvalidState('Socket is not defined');
		}

		return $this->webSocket;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function addParameter(string $key, $value): void
	{
		$this->parameters->offsetSet($key, $value);
	}

	#[Override]
	public function getParameter(string $key, mixed $default = null): mixed
	{
		return $this->parameters->offsetExists($key) ? $this->parameters->offsetGet($key) : $default;
	}

	#[Override]
	public function close(int|null $code = null): void
	{
		$this->webSocket->getProtocol()->close($this, $code);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function send($response): void
	{
		if ($response instanceof Responses\ControllerResponse) {
			$response = (string) $response;
		}

		$this->webSocket->getProtocol()->send($this, $response);
	}

	/**
	 * @param array<string> $roles
	 */
	#[Override]
	public function setIdentity(Identity\UserIdentity|null $identity, array $roles = []): void
	{
		$this->identity = $identity;
		$this->roles = $identity !== null ? $roles : [];
	}

	#[Override]
	public function getIdentity(): Identity\UserIdentity|null
	{
		return $this->identity;
	}

	/**
	 * @return array<string>
	 */
	#[Override]
	public function getRoles(): array
	{
		return $this->roles;
	}

}
