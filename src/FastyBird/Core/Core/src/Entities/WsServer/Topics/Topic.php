<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer\Topics;

use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Controllers\WebSockets\WampApplication;
use FastyBird\Core\Entities\WsServer as WebSocketsEntities;
use FastyBird\Core\Exceptions;
use Nette;
use Nette\Utils;
use Override;
use SplObjectStorage;
use Traversable;
use function assert;
use function count;
use function in_array;
use function is_string;
use function sprintf;

/**
 * A topic/channel containing connections that have subscribed to it
 */
final class Topic implements ITopic
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * If true the TopicManager will destroy this object if it's ever empty of connections
	 *
	 * @type bool
	 */
	private $autoDelete = false;

	private string $id;

	private SplObjectStorage $subscribers;

	/**
	 * @param string $topicId Unique ID for this object
	 */
	public function __construct(string $topicId)
	{
		$this->id = $topicId;
		$this->subscribers = new SplObjectStorage();
	}

	#[Override]
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	#[Override]
	public function broadcast(
		Responses\IResponse|string $message,
		array $exclude = [],
		array $eligible = [],
	): void
	{
		if (!is_string($message) && !$message instanceof Responses\IResponse) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'Provided message for broadcasting have to be string or instance of "%s"',
					Responses\IResponse::class,
				),
			);
		}

		$useEligible = (bool) count($eligible);

		foreach ($this->subscribers as $client) {
			assert($client instanceof WebSocketsEntities\IClient);
			if (in_array($client->getId(), $exclude, true)) {
				continue;
			}

			if ($useEligible && !in_array($client->getParameter('subscribedTopics'), $eligible, true)) {
				continue;
			}

			$client->send(Utils\Json::encode([WampApplication::MSG_EVENT, $this->id, (string) $message]));
		}
	}

	#[Override]
	public function has(WebSocketsEntities\IClient $client): bool
	{
		return $this->subscribers->offsetExists($client);
	}

	#[Override]
	public function add(WebSocketsEntities\IClient $client): void
	{
		$this->subscribers->offsetSet($client);
	}

	#[Override]
	public function remove(WebSocketsEntities\IClient $client): void
	{
		if ($this->subscribers->offsetExists($client)) {
			$this->subscribers->offsetUnset($client);
		}
	}

	#[Override]
	public function getIterator(): Traversable
	{
		return $this->subscribers;
	}

	#[Override]
	public function count(): int
	{
		return $this->subscribers->count();
	}

	#[Override]
	public function enableAutoDelete(): void
	{
		$this->autoDelete = true;
	}

	#[Override]
	public function disableAutoDelete(): void
	{
		$this->autoDelete = false;
	}

	#[Override]
	public function isAutoDeleteEnabled(): bool
	{
		return $this->autoDelete;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function __toString()
	{
		return $this->getId();
	}

}
