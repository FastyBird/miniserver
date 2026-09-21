<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use FastyBird\Core\Clients\WsServer as Clients;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Http\WebSockets as Http;
use FastyBird\Core\Routing\WebSockets as Router;
use FastyBird\Core\Server\WsServer as Server;
use Nette;
use Psr\Log;
use Throwable;
use function array_merge;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Application which run on server and provide creating controllers
 * with correctly params - convert message => control.
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @method onOpen(IApplication $application, Entities\IClient $client, Http\IRequest $httpRequest)
 * @method onClose(IApplication $application, Entities\IClient $client, Http\IRequest $httpRequest)
 * @method onMessage(IApplication $application, Entities\IClient $client, Http\IRequest $httpRequest, string $message)
 * @method onError(IApplication $application, Entities\IClient $client, Http\IRequest $httpRequest, Throwable $ex)
 */
abstract class Application implements IApplication
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public array $onOpen = [];

	public array $onClose = [];

	public array $onMessage = [];

	public array $onError = [];

	protected Log\LoggerInterface|Log\NullLogger|null $logger = null;

	public function __construct(
		protected Router\IRouter $router,
		protected Controller\IControllerFactory $controllerFactory,
		protected Clients\IStorage $clientsStorage,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function handleOpen(Entities\IClient $client, Http\IRequest $httpRequest): void
	{
		$this->logger->info(sprintf('New connection! (%s)', $client->getId()));

		$this->onOpen($this, $client, $httpRequest);
	}

	public function handleClose(Entities\IClient $client, Http\IRequest $httpRequest): void
	{
		$this->onClose($this, $client, $httpRequest);

		$this->logger->info(sprintf('Connection %s has disconnected', $client->getId()));
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function handleError(Entities\IClient $client, Http\IRequest $httpRequest, Throwable $ex): void
	{
		$this->logger->info(sprintf('An error (%s) has occurred: %s', $ex->getCode(), $ex->getMessage()));

		$code = $ex->getCode();

		$this->onError($this, $client, $httpRequest, $ex);

		if ($code >= 400 && $code < 600) {
			$this->close($client, $code);

		} else {
			$client->close();
		}
	}

	public function handleMessage(Entities\IClient $from, Http\IRequest $httpRequest, string $message): void
	{
		$this->onMessage($this, $from, $httpRequest, $message);
	}

	/**
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws WebSocketsExceptions\InvalidController
	 */
	protected function processMessage(Http\IRequest $httpRequest, array $parameters): Responses\IResponse|null
	{
		$appRequest = $this->router->match($httpRequest);

		if ($appRequest === null) {
			throw new WebSocketsExceptions\BadRequest('Invalid message - router cant create request.');
		}

		$appRequest->setParameters(array_merge($appRequest->getParameters(), $parameters));

		$controllerName = $appRequest->getControllerName();
		$controllerClass = $this->controllerFactory->getControllerClass($controllerName);

		if (!is_subclass_of($controllerClass, Controller\IController::class)) {
			throw new WebSocketsExceptions\BadRequest(
				sprintf('%s must be implementation of %s.', $controllerClass, Controller\IController::class),
			);
		}

		$controller = $this->controllerFactory->createController($controllerName);
		assert($controller instanceof Controller\IController);

		return $controller->run($appRequest);
	}

	/**
	 * Close a connection with an HTTP response
	 *
	 * @param int $code HTTP status code
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	protected function close(Entities\IClient $client, int $code = 400, array $additionalHeaders = []): void
	{
		$headers = array_merge([
			'X-Powered-By' => Server\Server::VERSION,
		], $additionalHeaders);

		$response = new Responses\ErrorResponse($code, $headers);

		$client->send($response);
		$client->close();
	}

}
