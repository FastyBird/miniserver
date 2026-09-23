<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer;

use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Entities\WebSockets;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use Nette\Security as NS;
use Nette\Utils;
use Override;
use React\Socket;

/**
 * Single client connection
 */
class Client implements IClient
{

	private NS\User|null $user = null;

	private bool $httpHeadersReceived = false;

	private string $httpBuffer = '';

	private string|null $remoteAddress = null;

	private Http\IRequest $httpRequest;

	private WebSockets\IWebSocket $webSocket;

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
	public function setHttpHeadersReceived(bool $state): void
	{
		$this->httpHeadersReceived = $state;
	}

	#[Override]
	public function isHttpHeadersReceived(): bool
	{
		return $this->httpHeadersReceived;
	}

	#[Override]
	public function setHttpBuffer(string $buffer): void
	{
		$this->httpBuffer = $buffer;
	}

	#[Override]
	public function getHttpBuffer(): string
	{
		return $this->httpBuffer;
	}

	#[Override]
	public function setRequest(Http\IRequest $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}

	#[Override]
	public function getRequest(): Http\IRequest
	{
		return clone $this->httpRequest;
	}

	#[Override]
	public function setWebSocket(WebSockets\IWebSocket $webSocket): void
	{
		$this->webSocket = $webSocket;
	}

	#[Override]
	public function getWebSocket(): WebSockets\IWebSocket
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
		if ($response instanceof Responses\IResponse) {
			$response = (string) $response;
		}

		$this->webSocket->getProtocol()->send($this, $response);
	}

	#[Override]
	public function setUser(NS\User $user): void
	{
		$this->user = $user;
	}

	#[Override]
	public function getUser(): NS\User|null
	{
		return $this->user;
	}

}
