<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Server;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use OverflowException;
use Override;
use Psr\EventDispatcher;
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
		private Controllers\Application $application,
		private Clients\Storage $clientsStorage,
		private EventDispatcher\EventDispatcherInterface $dispatcher,
	)
	{
		$this->protocolsProxy = new Encoding\ProtocolProxy();
		$this->protocolsProxy->enableProtocol(new Encoding\RFC6455());
		$this->protocolsProxy->enableProtocol(new Encoding\HyBi10());

		$this->requestFactory = new Handshake\RequestFactory();
	}

	#[Override]
	public function handleOpen(Entities\Client $client): void
	{
		$client->httpHeadersReceived = false;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	#[Override]
	public function handleMessage(Entities\Client $client, string $message): void
	{
		if (!$client->httpHeadersReceived) {
			$client->httpBuffer .= $message;

			try {
				if (($httpRequest = $this->requestFactory->createHttpRequest($client->httpBuffer)) === null) {
					return;
				}

				$client->setRequest($httpRequest);
				$client->httpBuffer = '';

			} catch (OverflowException) {
				$this->close($client, Handshake\WampResponse::S413_REQUEST_ENTITY_TOO_LARGE);

				return;
			}

			$client->httpHeadersReceived = true;

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
	 * @throws WebSocketsExceptions\Storage
	 */
	#[Override]
	public function handleClose(Entities\Client $client): void
	{
		if ($client->httpHeadersReceived) {
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
	public function handleError(Entities\Client $client, Throwable $ex): void
	{
		if ($client->httpHeadersReceived) {
			$this->connectionError($client, $ex);

		} else {
			$this->close($client, Handshake\WampResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	private function connectionOpen(Entities\Client $client, Handshake\Request $httpRequest): void
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
			$this->close($client, Handshake\WampResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws WebSocketsExceptions\Storage
	 */
	private function connectionClose(Entities\Client $client): void
	{
		try {
			// Call service event
			$this->dispatcher->dispatch(new Events\ClientDisconnected($client, $client->getRequest()));

			// Call application event
			$this->application->handleClose($client, $client->getRequest());

			$this->clientsStorage->removeClient($client->getId());

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Handshake\WampResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function connectionError(Entities\Client $client, Throwable $ex): void
	{
		try {
			$webSocket = $client->getWebSocket();

			if ($webSocket->established) {
				// Call service event
				$this->dispatcher->dispatch(new Events\ClientFailed($client, $client->getRequest()));

				// Call application event
				$this->application->handleError($client, $client->getRequest(), $ex);

				return;
			}

			$client->getConnection()->end();

		} catch (WebSocketsExceptions\ClientNotFound) {
			$this->close($client, Handshake\WampResponse::S500_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws UnderflowException
	 */
	private function connectionMessage(Entities\Client $client, string $message): void
	{
		$webSocket = $client->getWebSocket();

		if ($webSocket->closing) {
			return;
		}

		if ($webSocket->established === true) {
			// Call service event
			$this->dispatcher->dispatch(new Events\MessageReceived($client, $client->getRequest(), $message));

			// A subscriber that rejects the client -- e.g. its access token has expired or was
			// revoked since the handshake -- closes it, and the message must not reach the
			// application. Read back through the client, which is what the subscribers acted on.
			if ($client->getWebSocket()->closing) {
				return;
			}

			$webSocket->getProtocol()->handleMessage($client, $this->application, $message);

			// Call service event
			$this->dispatcher->dispatch(new Events\MessageProcessed($client, $client->getRequest()));

			return;
		}

		$this->attemptUpgrade($client);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	private function attemptUpgrade(Entities\Client $client): mixed
	{
		$httpRequest = $client->getRequest();
		assert($httpRequest instanceof Handshake\Request);

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

		if ($response->getCode() !== Handshake\WampResponse::S101_SWITCHING_PROTOCOLS) {
			$client->getConnection()->end();

			return null;
		}

		$webSocket->established = true;

		// Call service event
		$this->dispatcher->dispatch(new Events\ClientConnected($client, $httpRequest));

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
	private function close(Entities\Client $client, int $code = 400, mixed $body = null): void
	{
		$response = new Handshake\WampResponse($code, [
			'Sec-WebSocket-Version' => $this->protocolsProxy->getSupportedProtocols(),
			'X-Powered-By' => ServerRuntime::VERSION,
		], $body);

		$client->getConnection()->write((string) $response);
		$client->getConnection()->end();
	}

}
