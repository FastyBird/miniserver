<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Server;

use Closure;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use Nette\Utils;
use OverflowException;
use Override;
use Throwable;
use TypeError;
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
 */
final class Wrapper implements ServerWrapper
{

	/** @var array<Closure(Entities\ConnectedClient $client, Handshake\IRequest $request): void> */
	public array $onClientConnected = [];

	/** @var array<Closure(Entities\ConnectedClient $client, Handshake\IRequest $request): void> */
	public array $onClientDisconnected = [];

	/** @var array<Closure(Entities\ConnectedClient $client, Handshake\IRequest $request): void> */
	public array $onClientError = [];

	/** @var array<Closure(Entities\ConnectedClient $client, Handshake\IRequest $request, string $message): void> */
	public array $onIncomingMessage = [];

	/** @var array<Closure(Entities\ConnectedClient $client, Handshake\IRequest $request): void> */
	public array $onAfterIncomingMessage = [];

	/**
	 * Flag if we have checked the decorated application for sub-protocols
	 */
	private bool $isSpGenerated = false;

	/**
	 * Holder of accepted protocols
	 */
	private array $acceptedSubProtocols = [];

	private Encoding\ProtocolProxy $protocolsProxy;

	private Handshake\RequestFactory $requestFactory;

	public function __construct(
		private Controllers\Dispatcher $application,
		private Clients\IStorage $clientsStorage,
	)
	{
		$this->protocolsProxy = new Encoding\ProtocolProxy();
		$this->protocolsProxy->enableProtocol(new Encoding\RFC6455());
		$this->protocolsProxy->enableProtocol(new Encoding\HyBi10());

		$this->requestFactory = new Handshake\RequestFactory();
	}

	#[Override]
	public function handleOpen(Entities\ConnectedClient $client): void
	{
		$client->setHttpHeadersReceived(false);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	#[Override]
	public function handleMessage(Entities\ConnectedClient $client, string $message): void
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
				$this->close($client, Handshake\IResponse::S413_REQUEST_ENTITY_TOO_LARGE);

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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	#[Override]
	public function handleClose(Entities\ConnectedClient $client): void
	{
		if ($client->isHttpHeadersReceived()) {
			$this->connectionClose($client);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	#[Override]
	public function handleError(Entities\ConnectedClient $client, Throwable $ex): void
	{
		if ($client->isHttpHeadersReceived()) {
			$this->connectionError($client, $ex);

		} else {
			$this->close($client, Handshake\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	private function connectionOpen(Entities\ConnectedClient $client, Handshake\IRequest $httpRequest): void
	{
		if (!$this->protocolsProxy->isProtocolEnabled($httpRequest)) {
			$this->close($client);

			return;
		}

		try {
			$protocol = $this->protocolsProxy->getProtocol($httpRequest);

			$webSocket = new Entities\WebSocket(false, false, $protocol);

			$client->setWebSocket($webSocket);

			$this->attemptUpgrade($client);

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Handshake\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	private function connectionClose(Entities\ConnectedClient $client): void
	{
		try {
			// Call service event
			Utils\Arrays::invoke($this->onClientDisconnected, $client, $client->getRequest());

			// Call application event
			$this->application->handleClose($client, $client->getRequest());

			$this->clientsStorage->removeClient($client->getId());

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Handshake\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function connectionError(Entities\ConnectedClient $client, Throwable $ex): void
	{
		try {
			$webSocket = $client->getWebSocket();

			if ($webSocket->isEstablished()) {
				// Call service event
				Utils\Arrays::invoke($this->onClientError, $client, $client->getRequest());

				// Call application event
				$this->application->handleError($client, $client->getRequest(), $ex);

				return;
			}

			$client->getConnection()->end();

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Handshake\IResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	private function connectionMessage(Entities\ConnectedClient $client, string $message): void
	{
		$webSocket = $client->getWebSocket();

		if ($webSocket->isClosing()) {
			return;
		}

		if ($webSocket->isEstablished() === true) {
			// Call service event
			Utils\Arrays::invoke($this->onIncomingMessage, $client, $client->getRequest(), $message);

			// A subscriber that rejects the client -- e.g. its access token has expired or was
			// revoked since the handshake -- closes it, and the message must not reach the
			// application. Read back through the client, which is what the subscribers acted on.
			if ($client->getWebSocket()->isClosing()) {
				return;
			}

			$webSocket->getProtocol()->handleMessage($client, $this->application, $message);

			// Call service event
			Utils\Arrays::invoke($this->onAfterIncomingMessage, $client, $client->getRequest());

			return;
		}

		$this->attemptUpgrade($client);
	}

	private function attemptUpgrade(Entities\ConnectedClient $client): mixed
	{
		$httpRequest = $client->getRequest();
		assert($httpRequest instanceof Handshake\IRequest);

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

		$response->addHeader('X-Powered-By', ServerRuntime::VERSION);

		$client->getConnection()->write((string) $response);

		if ($response->getCode() !== Handshake\IResponse::S101_SWITCHING_PROTOCOLS) {
			$client->getConnection()->end();

			return null;
		}

		$webSocket->setEstablished(true);

		// Call service event
		Utils\Arrays::invoke($this->onClientConnected, $client, $httpRequest);

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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	private function close(Entities\ConnectedClient $client, int $code = 400, mixed $body = null): void
	{
		$response = new Handshake\WampResponse($code, [
			'Sec-WebSocket-Version' => $this->protocolsProxy->getSupportedProtocols(),
			'X-Powered-By' => ServerRuntime::VERSION,
		], $body);

		$client->getConnection()->write((string) $response);
		$client->getConnection()->end();
	}

}
