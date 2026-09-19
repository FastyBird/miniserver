<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Entities\Clients;

use FastyBird\Library\WebSockets\Application\Responses;
use FastyBird\Library\WebSockets\Entities;
use FastyBird\Library\WebSockets\Exceptions;
use FastyBird\Library\WebSockets\Http;
use Nette;
use Nette\Security as NS;
use Nette\Utils;
use React\Socket;

/**
 * Single client connection
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class Client implements IClient
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private NS\User|null $user = null;

	private bool $httpHeadersReceived = false;

	private string $httpBuffer = '';

	private string|null $remoteAddress = null;

	private Http\IRequest $httpRequest;

	private Entities\WebSockets\IWebSocket $webSocket;

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

	public function setHttpHeadersReceived(bool $state): void
	{
		$this->httpHeadersReceived = $state;
	}

	public function isHttpHeadersReceived(): bool
	{
		return $this->httpHeadersReceived;
	}

	public function setHttpBuffer(string $buffer): void
	{
		$this->httpBuffer = $buffer;
	}

	public function getHttpBuffer(): string
	{
		return $this->httpBuffer;
	}

	public function setRequest(Http\IRequest $httpRequest): void
	{
		$this->httpRequest = $httpRequest;
	}

	public function getRequest(): Http\IRequest
	{
		return clone $this->httpRequest;
	}

	public function setWebSocket(Entities\WebSockets\IWebSocket $webSocket): void
	{
		$this->webSocket = $webSocket;
	}

	public function getWebSocket(): Entities\WebSockets\IWebSocket
	{
		if ($this->webSocket === null) {
			throw new Exceptions\InvalidState('Socket is not defined');
		}

		return $this->webSocket;
	}

	/**
	 * {@inheritDoc}
	 */
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

	/**
	 * {@inheritDoc}
	 */
	public function send($response): void
	{
		if ($response instanceof Responses\IResponse) {
			$response = (string) $response;
		}

		$this->webSocket->getProtocol()->send($this, $response);
	}

	public function setUser(NS\User $user): void
	{
		$this->user = $user;
	}

	public function getUser(): NS\User|null
	{
		return $this->user;
	}

}
