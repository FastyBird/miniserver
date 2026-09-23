<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use Closure;
use FastyBird\Core\Clients\WsServer as WebSocketsClients;
use FastyBird\Core\Entities\WebSockets\PushMessages;
use FastyBird\Core\Entities\WsServer as WebSocketsEntities;
use FastyBird\Core\Entities\WsServer\Topics as TopicEntities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Http as WebSocketsHttp;
use FastyBird\Core\Routing as WebSocketsRouter;
use FastyBird\Core\Server\WsServer as WebSocketsServer;
use FastyBird\Core\Topics\WsServer as Topics;
use Nette\Http;
use Nette\Utils;
use Override;
use Psr\Log;
use SplObjectStorage;
use Throwable;
use function array_key_exists;
use function array_shift;
use function array_values;
use function count;
use function is_array;
use function ltrim;
use function mt_rand;
use function rtrim;
use function sprintf;
use function str_replace;
use function uniqid;

/**
 * Application which run on server and provide creating controllers
 * with correctly params - convert message => control
 */
final class WampApplication extends Application implements IWampApplication
{

	public const int MSG_WELCOME = 0;

	public const int MSG_PREFIX = 1;

	public const int MSG_CALL = 2;

	public const int MSG_CALL_RESULT = 3;

	public const int MSG_CALL_ERROR = 4;

	public const int MSG_SUBSCRIBE = 5;

	public const int MSG_UNSUBSCRIBE = 6;

	public const int MSG_PUBLISH = 7;

	public const int MSG_EVENT = 8;

	/** @var array<Closure(PushMessages\IMessage $message, string $provider, TopicEntities\ITopic $topic): void> */
	public array $onPush = [];

	private SplObjectStorage $subscriptions;

	public function __construct(
		private Topics\IStorage $topicsStorage,
		WebSocketsRouter\IWampRouter $router,
		Controller\IControllerFactory $controllerFactory,
		WebSocketsClients\IStorage $clientsStorage,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct($router, $controllerFactory, $clientsStorage, $logger);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Utils\JsonException
	 */
	#[Override]
	public function handleOpen(WebSocketsEntities\IClient $client, WebSocketsHttp\IRequest $httpRequest): void
	{
		$client->addParameter('wampSession', str_replace('.', '', uniqid((string) mt_rand(), true)));

		// Send welcome handshake
		$client->send(Utils\Json::encode([
			self::MSG_WELCOME,
			$client->getParameter('wampSession'),
			1,
			WebSocketsServer\Server::VERSION,
		]));

		$this->subscriptions = new SplObjectStorage();

		parent::handleOpen($client, $httpRequest);
	}

	#[Override]
	public function handleClose(WebSocketsEntities\IClient $client, WebSocketsHttp\IRequest $httpRequest): void
	{
		parent::handleClose($client, $httpRequest);

		foreach ($this->topicsStorage as $topic) {
			$this->cleanTopic($topic, $client);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws WebSocketsExceptions\Terminate
	 */
	#[Override]
	public function handleMessage(
		WebSocketsEntities\IClient $client,
		WebSocketsHttp\IRequest $httpRequest,
		string $message,
	): void
	{
		parent::handleMessage($client, $httpRequest, $message);

		try {
			$json = Utils\Json::decode($message, Utils\Json::FORCE_ARRAY);

			if ($json === null || !is_array($json) || $json !== array_values($json)) {
				throw new Exceptions\InvalidArgument('Invalid WAMP message format');
			}

			switch ($json[0]) {
				case self::MSG_PREFIX:
					$prefixes = $client->getParameter('prefixes', []);
					$prefixes[$json[1]] = $json[2];

					$client->addParameter('prefixes', $prefixes);

					$client->send(Utils\Json::encode([self::MSG_PREFIX, $json[1], (string) $json[2]]));

					break;

				// RPC action
				case self::MSG_CALL:
					array_shift($json);

					$rpcId = array_shift($json);
					$topicId = array_shift($json);

					$topic = $this->getTopic($topicId);

					if (count($json) === 1 && is_array($json[0])) {
						$json = $json[0];
					}

					$httpRequest = $this->modifyRequest($httpRequest, $topic, 'call');

					try {
						$response = $this->processMessage($httpRequest, [
							'client' => $client,
							'topic' => $topic,
							'rpcId' => $rpcId,
							'args' => $json,
						]);

						$client->send(Utils\Json::encode([self::MSG_CALL_RESULT, $rpcId, $response->create()]));

					} catch (WebSocketsExceptions\Terminate $ex) {
						throw $ex;
					} catch (Throwable $ex) {
						$data = [
							self::MSG_CALL_ERROR,
							$rpcId,
							$topicId,
							$ex->getMessage(),
							[
								'code' => $ex->getCode(),
								'params' => $json,
							],
						];

						$client->send(Utils\Json::encode($data));
					}

					$this->logger->info(
						sprintf('Connection %s has called RPC on %s topic', $client->getId(), $topic->getId()),
					);

					break;

				// Subscribe to topic
				case self::MSG_SUBSCRIBE:
					$topic = $this->getTopic($json[1]);

					$subscribedTopics = $client->getParameter('subscribedTopics', new SplObjectStorage());

					if ($subscribedTopics->offsetExists($topic)) {
						return;
					}

					$topic = $this->topicsStorage->getTopic($topic->getId());
					$topic->add($client);

					$this->topicsStorage->addTopic($topic->getId(), $topic);

					$subscribedTopics->offsetSet($topic);

					$client->addParameter('subscribedTopics', $subscribedTopics);

					$httpRequest = $this->modifyRequest($httpRequest, $topic, 'subscribe');

					$this->processMessage($httpRequest, [
						'client' => $client,
						'topic' => $topic,
					]);

					$this->logger->info(
						sprintf('Connection %s has subscribed to %s', $client->getId(), $topic->getId()),
					);

					break;

				// Unsubscribe from topic
				case self::MSG_UNSUBSCRIBE:
					$topic = $this->getTopic($json[1]);

					$subscribedTopics = $client->getParameter('subscribedTopics', new SplObjectStorage());

					if (!$subscribedTopics->offsetExists($topic)) {
						return;
					}

					$this->cleanTopic($topic, $client);

					$httpRequest = $this->modifyRequest($httpRequest, $topic, 'unsubscribe');

					$this->processMessage($httpRequest, [
						'client' => $client,
						'topic' => $topic,
					]);

					$this->logger->info(
						sprintf('Connection %s has unsubscribed from %s', $client->getId(), $topic->getId()),
					);

					break;

				// Publish to topic
				case self::MSG_PUBLISH:
					$topic = $this->getTopic($json[1]);

					$exclude = (array_key_exists(3, $json) ? $json[3] : null);

					if (!is_array($exclude)) {
						$exclude = (bool) $exclude === true ? [$client->getParameter('wampSession')] : [];
					}

					$eligible = (array_key_exists(4, $json) ? $json[4] : []);

					$event = $json[2];

					$httpRequest = $this->modifyRequest($httpRequest, $topic, 'publish');

					$this->processMessage($httpRequest, [
						'client' => $client,
						'topic' => $topic,
						'event' => $event,
						'exclude' => $exclude,
						'eligible' => $eligible,
					]);

					$this->logger->info(
						sprintf('Connection %s has published to %s topic', $client->getId(), $topic->getId()),
					);

					break;
				default:
					throw new Exceptions\InvalidArgument('Invalid WAMP message type');
			}
		} catch (WebSocketsExceptions\Terminate $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			$this->logger->error(sprintf('An error (%s) has occurred: %s', $ex->getCode(), $ex->getMessage()));

			$client->close(1_007);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws WebSocketsExceptions\Terminate
	 */
	#[Override]
	public function handlePush(PushMessages\IMessage $message, string $provider): void
	{
		try {
			$topic = $this->getTopic($message->getTopic());

			$url = new Http\Url($message->getTopic());
			$action = $url->getQueryParameter(Controller\Controller::ACTION_KEY);

			if ($action === null || $action === Controller\Controller::DEFAULT_ACTION) {
				$url->setQueryParameter(Controller\Controller::ACTION_KEY, 'push');
			}

			$httpRequest = new WebSocketsHttp\Request(
				new Http\UrlScript($url),
				[],
				[],
				[],
				[],
				WebSocketsHttp\IRequest::GET,
			);

			$this->processMessage($httpRequest, [
				'topic' => $topic,
				'data' => $message->getData(),
				'message' => $message,
			]);

			$this->logger->info(sprintf('Message was pushed to %s topic', $topic->getId()));

			Utils\Arrays::invoke($this->onPush, $message, $provider, $topic);

		} catch (WebSocketsExceptions\Terminate $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			$context = [
				'provider' => $provider,
				'topic' => $message->getTopic(),
				'data' => $message->getData(),
			];

			$this->logger->error(
				sprintf('An error (%s) has occurred: %s', $ex->getCode(), $ex->getMessage()),
				$context,
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getSubProtocols(): array
	{
		return ['wamp'];
	}

	private function getTopic(string $topic): TopicEntities\ITopic
	{
		if (!$this->topicsStorage->hasTopic($topic)) {
			$this->topicsStorage->addTopic($topic, new TopicEntities\Topic($topic));
		}

		return $this->topicsStorage->getTopic($topic);
	}

	private function cleanTopic(TopicEntities\ITopic $topic, WebSocketsEntities\IClient $client): void
	{
		$subscribedTopics = $client->getParameter('subscribedTopics', new SplObjectStorage());

		if ($subscribedTopics->offsetExists($topic)) {
			$subscribedTopics->offsetUnset($topic);
		}

		$topic = $this->topicsStorage->getTopic($topic->getId());
		$topic->remove($client);

		$this->topicsStorage->addTopic($topic->getId(), $topic);

		if ($topic->isAutoDeleteEnabled() && $topic->count() === 0) {
			$this->topicsStorage->removeTopic($topic->getId());
		}
	}

	private function modifyRequest(
		WebSocketsHttp\IRequest $httpRequest,
		TopicEntities\ITopic $topic,
		string $action,
	): WebSocketsHttp\IRequest
	{
		$url = new Http\Url((string) $httpRequest->getUrl());
		$url->setPath(rtrim($url->getPath(), '/') . '/' . ltrim($topic->getId(), '/'));

		$parsedAction = $url->getQueryParameter(Controller\Controller::ACTION_KEY);

		if ($parsedAction === null || $parsedAction === Controller\Controller::DEFAULT_ACTION) {
			$url->setQueryParameter(Controller\Controller::ACTION_KEY, $action);
		}

		$httpRequest->setUrl(new Http\UrlScript($url));

		return $httpRequest;
	}

}
