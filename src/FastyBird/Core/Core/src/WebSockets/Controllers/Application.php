<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

use Closure;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Wamp;
use Nette\Utils;
use Override;
use Psr\Log;
use Throwable;
use function array_merge;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Application which run on server and provide creating controllers
 * with correctly params - convert message => control.
 */
abstract class Application implements Dispatcher
{

	/** @var array<Closure(self $application, Entities\ConnectedClient $client, Handshake\IRequest $httpRequest): void> */
	public array $onOpen = [];

	/** @var array<Closure(self $application, Entities\ConnectedClient $client, Handshake\IRequest $httpRequest): void> */
	public array $onClose = [];

	/** @var array<Closure(self $application, Entities\ConnectedClient $from, Handshake\IRequest $httpRequest, string $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(self $application, Entities\ConnectedClient $client, Handshake\IRequest $httpRequest, Throwable $ex): void> */
	public array $onError = [];

	protected Log\LoggerInterface|Log\NullLogger|null $logger = null;

	public function __construct(
		protected Wamp\WampRouter $router,
		protected IControllerFactory $controllerFactory,
		protected Clients\IStorage $clientsStorage,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	#[Override]
	public function handleOpen(Entities\ConnectedClient $client, Handshake\IRequest $httpRequest): void
	{
		$this->logger->info(sprintf('New connection! (%s)', $client->getId()));

		Utils\Arrays::invoke($this->onOpen, $this, $client, $httpRequest);
	}

	#[Override]
	public function handleClose(Entities\ConnectedClient $client, Handshake\IRequest $httpRequest): void
	{
		Utils\Arrays::invoke($this->onClose, $this, $client, $httpRequest);

		$this->logger->info(sprintf('Connection %s has disconnected', $client->getId()));
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function handleError(Entities\ConnectedClient $client, Handshake\IRequest $httpRequest, Throwable $ex): void
	{
		$this->logger->info(sprintf('An error (%s) has occurred: %s', $ex->getCode(), $ex->getMessage()));

		$code = $ex->getCode();

		Utils\Arrays::invoke($this->onError, $this, $client, $httpRequest, $ex);

		if ($code >= 400 && $code < 600) {
			$this->close($client, $code);

		} else {
			$client->close();
		}
	}

	#[Override]
	public function handleMessage(
		Entities\ConnectedClient $from,
		Handshake\IRequest $httpRequest,
		string $message,
	): void
	{
		Utils\Arrays::invoke($this->onMessage, $this, $from, $httpRequest, $message);
	}

	/**
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws Exceptions\InvalidController
	 */
	protected function processMessage(
		Handshake\IRequest $httpRequest,
		array $parameters,
	): Responses\ControllerResponse|null
	{
		$appRequest = $this->router->match($httpRequest);

		if ($appRequest === null) {
			throw new WebSocketsExceptions\BadRequest('Invalid message - router cant create request.');
		}

		$appRequest->setParameters(array_merge($appRequest->getParameters(), $parameters));

		$controllerName = $appRequest->getControllerName();
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
	 * @throws Exceptions\InvalidArgument
	 */
	protected function close(Entities\ConnectedClient $client, int $code = 400, array $additionalHeaders = []): void
	{
		$headers = array_merge([
			'X-Powered-By' => Server\ServerRuntime::VERSION,
		], $additionalHeaders);

		$response = new Responses\ErrorResponse($code, $headers);

		$client->send($response);
		$client->close();
	}

}
