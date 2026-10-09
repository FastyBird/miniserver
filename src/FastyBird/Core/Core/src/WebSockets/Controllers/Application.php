<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Wamp;
use Psr\EventDispatcher;
use Psr\Log;
use ReflectionException;
use Throwable;
use function array_merge;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Application which run on server and provide creating controllers
 * with correctly params - convert message => control.
 */
abstract class Application
{

	protected Log\LoggerInterface|Log\NullLogger|null $logger = null;

	public function __construct(
		protected Wamp\WampRouter $router,
		protected ControllerFactory $controllerFactory,
		protected Clients\Storage $clientsStorage,
		private EventDispatcher\EventDispatcherInterface $dispatcher,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * When a new connection is opened it will be passed to this method
	 */
	public function handleOpen(Entities\Client $client, Handshake\Request $httpRequest): void
	{
		$this->logger->info(sprintf('New connection! (%s)', $client->getId()));

		$this->dispatcher->dispatch(new Events\ConnectionOpened($this, $client, $httpRequest));
	}

	/**
	 * This is called before or after a socket is closed (depends on how it's closed)
	 * SendMessage to $client will not result in an error if it has already been closed
	 */
	public function handleClose(Entities\Client $client, Handshake\Request $httpRequest): void
	{
		$this->dispatcher->dispatch(new Events\ConnectionClosed($this, $client, $httpRequest));

		$this->logger->info(sprintf('Connection %s has disconnected', $client->getId()));
	}

	/**
	 * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
	 * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
	 *
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function handleError(Entities\Client $client, Handshake\Request $httpRequest, Throwable $ex): void
	{
		$this->logger->info(sprintf('An error (%s) has occurred: %s', $ex->getCode(), $ex->getMessage()));

		$code = $ex->getCode();

		$this->dispatcher->dispatch(new Events\ApplicationFailed($this, $client, $httpRequest, $ex));

		if ($code >= 400 && $code < 600) {
			$this->close($client, $code);

		} else {
			$client->close();
		}
	}

	/**
	 * Triggered when a client sends data through the socket
	 */
	public function handleMessage(
		Entities\Client $from,
		Handshake\Request $httpRequest,
		string $message,
	): void
	{
		$this->dispatcher->dispatch(new Events\ApplicationMessageReceived($this, $from, $httpRequest, $message));
	}

	/**
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws CoreExceptions\InvalidController
	 * @throws ReflectionException
	 */
	protected function processMessage(
		Handshake\Request $httpRequest,
		array $parameters,
	): Responses\ControllerResponse|null
	{
		$appRequest = $this->router->match($httpRequest);

		if ($appRequest === null) {
			throw new WebSocketsExceptions\BadRequest('Invalid message - router cant create request.');
		}

		$appRequest->parameters = array_merge($appRequest->parameters, $parameters);

		$controllerName = $appRequest->controllerName;
		$controllerClass = $this->controllerFactory->getControllerClass($controllerName);

		if (!is_subclass_of($controllerClass, RequestController::class)) {
			throw new WebSocketsExceptions\BadRequest(
				sprintf('%s must be implementation of %s.', $controllerClass, RequestController::class),
			);
		}

		$controller = $this->controllerFactory->createController($controllerName);
		assert($controller instanceof RequestController);

		return $controller->run($appRequest);
	}

	/**
	 * Close a connection with an HTTP response
	 *
	 * @param int $code HTTP status code
	 *
	 * @throws CoreExceptions\InvalidArgument
	 */
	protected function close(Entities\Client $client, int $code = 400, array $additionalHeaders = []): void
	{
		$headers = array_merge([
			'X-Powered-By' => Server\ServerRuntime::VERSION,
		], $additionalHeaders);

		$response = new Responses\ErrorResponse($code, $headers);

		$client->send($response);
		$client->close();
	}

}
