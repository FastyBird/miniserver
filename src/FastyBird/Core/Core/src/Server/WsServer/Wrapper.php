<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use FastyBird\Core\Clients\WsServer as Clients;
use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Encoding\WebSockets as Protocols;
use FastyBird\Core\Entities\WebSockets as WebSocketEntities;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Http;
use Nette;
use OverflowException;
use Throwable;
use UnderflowException;
use function array_flip;
use function array_key_exists;
use function assert;
use function preg_quote;
use function preg_split;
use function strpos;
use function strtolower;
use function trim;

/**
 * WebSockets server application wrapper
 * Purpose of this class is to create better interface for connection objects
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @method onClientConnected(Entities\IClient $client, Http\IRequest $httpRequest)
 * @method onClientDisconnected(Entities\IClient $client, Http\IRequest $httpRequest)
 * @method onClientError(Entities\IClient $client, Http\IRequest $httpRequest)
 * @method onIncomingMessage(Entities\IClient $client, Http\IRequest $httpRequest, string $message)
 * @method onAfterIncomingMessage(Entities\IClient $client, Http\IRequest $httpRequest)
 */
final class Wrapper implements IWrapper
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public array $onClientConnected = [];

	public array $onClientDisconnected = [];

	public array $onClientError = [];

	public array $onIncomingMessage = [];

	public array $onAfterIncomingMessage = [];

	/**
	 * Flag if we have checked the decorated application for sub-protocols
	 */
	private bool $isSpGenerated = false;

	/**
	 * Holder of accepted protocols
	 */
	private array $acceptedSubProtocols = [];

	private Protocols\ProtocolProxy $protocolsProxy;

	private Http\RequestFactory $requestFactory;

	public function __construct(
		private Application\IApplication $application,
		private Clients\IStorage $clientsStorage,
	)
	{
		$this->protocolsProxy = new Protocols\ProtocolProxy();
		$this->protocolsProxy->enableProtocol(new Protocols\RFC6455());
		$this->protocolsProxy->enableProtocol(new Protocols\HyBi10());

		$this->requestFactory = new Http\RequestFactory();
	}

	public function handleOpen(Entities\IClient $client): void
	{
		$client->setHttpHeadersReceived(false);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	public function handleMessage(Entities\IClient $client, string $message): void
	{
		if (!$client->isHttpHeadersReceived()) {
			$client->setHttpBuffer($client->getHttpBuffer() . $message);

			try {
				if (($httpRequest = $this->requestFactory->createHttpRequest($client->getHttpBuffer())) === null) {
					return;
				}

				$client->setRequest($httpRequest);
				$client->setHttpBuffer('');

			} catch (OverflowException) {
				$this->close($client, Http\IResponse::S413_REQUEST_ENTITY_TOO_LARGE);

				return;
			}

			$client->setHttpHeadersReceived(true);

			$this->connectionOpen($client, $httpRequest);

			return;
		}

		$this->connectionMessage($client, $message);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function handleClose(Entities\IClient $client): void
	{
		if ($client->isHttpHeadersReceived()) {
			$this->connectionClose($client);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function handleError(Entities\IClient $client, Throwable $ex): void
	{
		if ($client->isHttpHeadersReceived()) {
			$this->connectionError($client, $ex);

		} else {
			$this->close($client, Http\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function connectionOpen(Entities\IClient $client, Http\IRequest $httpRequest): void
	{
		if (!$this->protocolsProxy->isProtocolEnabled($httpRequest)) {
			$this->close($client);

			return;
		}

		try {
			$protocol = $this->protocolsProxy->getProtocol($httpRequest);

			$webSocket = new WebSocketEntities\WebSocket(false, false, $protocol);

			$client->setWebSocket($webSocket);

			$this->attemptUpgrade($client);

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Http\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function connectionClose(Entities\IClient $client): void
	{
		try {
			// Call service event
			$this->onClientDisconnected($client, $client->getRequest());

			// Call application event
			$this->application->handleClose($client, $client->getRequest());

			$this->clientsStorage->removeClient($client->getId());

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Http\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function connectionError(Entities\IClient $client, Throwable $ex): void
	{
		try {
			$webSocket = $client->getWebSocket();

			if ($webSocket->isEstablished()) {
				// Call service event
				$this->onClientError($client, $client->getRequest());

				// Call application event
				$this->application->handleError($client, $client->getRequest(), $ex);

				return;
			}

			$client->getConnection()->end();

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Http\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	private function connectionMessage(Entities\IClient $client, string $message): void
	{
		$webSocket = $client->getWebSocket();

		if ($webSocket->isClosing()) {
			return;
		}

		if ($webSocket->isEstablished() === true) {
			// Call service event
			$this->onIncomingMessage($client, $client->getRequest(), $message);

			$webSocket->getProtocol()->handleMessage($client, $this->application, $message);

			// Call service event
			$this->onAfterIncomingMessage($client, $client->getRequest());

			return;
		}

		$this->attemptUpgrade($client);
	}

	private function attemptUpgrade(Entities\IClient $client): mixed
	{
		$httpRequest = $client->getRequest();
		assert($httpRequest instanceof Http\IRequest);

		$webSocket = $client->getWebSocket();

		try {
			$response = $webSocket->getProtocol()->doHandshake($httpRequest);

		} catch (UnderflowException) {
			return null;
		}

		if (($subHeader = $httpRequest->getHeader('Sec-WebSocket-Protocol')) !== null) {
			$values = [];

			if (strpos($subHeader, ',') !== false) {
				// Explode on glue when the glue is not inside of a comma
				foreach (preg_split('/' . preg_quote(',') . '(?=([^"]*"[^"]*")*[^"]*$)/', $subHeader) as $v) {
					$values[] = strtolower(trim($v));
				}
			} elseif (trim($subHeader) !== '') {
				$values[] = strtolower(trim($subHeader));
			}

			if (($agreedSubProtocols = $this->getSubProtocolString($values)) !== '') {
				$response->addHeader('Sec-WebSocket-Protocol', $agreedSubProtocols);
			}
		}

		$response->addHeader('X-Powered-By', Server::VERSION);

		$client->getConnection()->write((string) $response);

		if ($response->getCode() !== Http\IResponse::S101_SWITCHING_PROTOCOLS) {
			$client->getConnection()->end();

			return null;
		}

		$webSocket->setEstablished(true);

		// Call service event
		$this->onClientConnected($client, $httpRequest);

		// Call application event
		return $this->application->handleOpen($client, $httpRequest);
	}

	private function getSubProtocolString(array $httpRequested = []): string
	{
		if ($httpRequested !== []) {
			foreach ($httpRequested as $sub) {
				if ($this->isSubProtocolSupported($sub)) {
					return $sub;
				}
			}
		}

		return '';
	}

	private function isSubProtocolSupported(string $name): bool
	{
		if (!$this->isSpGenerated) {
			$this->acceptedSubProtocols = array_flip($this->application->getSubProtocols());

			$this->isSpGenerated = true;
		}

		return array_key_exists($name, $this->acceptedSubProtocols);
	}

	/**
	 * Close a connection with an HTTP response
	 *
	 * @param int $code HTTP status code
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function close(Entities\IClient $client, int $code = 400, mixed $body = null): void
	{
		$response = new Http\WampResponse($code, [
			'Sec-WebSocket-Version' => $this->protocolsProxy->getSupportedProtocols(),
			'X-Powered-By' => Server::VERSION,
		], $body);

		$client->getConnection()->write((string) $response);
		$client->getConnection()->end();
	}

}
