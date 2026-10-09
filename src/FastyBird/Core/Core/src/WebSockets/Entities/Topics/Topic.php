<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities\Topics;

use Countable;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Entities;
use IteratorAggregate;
use Nette\Utils;
use Override;
use SplObjectStorage;
use Traversable;
use function count;
use function in_array;
use function is_string;
use function sprintf;

/**
 * A topic/channel containing connections that have subscribed to it
 *
 * @implements IteratorAggregate<int, Entities\Client>
 */
final class Topic implements IteratorAggregate, Countable
{

	/**
	 * If true the TopicManager will destroy this object if it's ever empty of connections
	 *
	 * @type bool
	 */
	private $autoDelete = false;

	private string $id;

	/** @var SplObjectStorage<Entities\Client, mixed> */
	private SplObjectStorage $subscribers;

	/**
	 * @param string $topicId Unique ID for this object
	 */
	public function __construct(string $topicId)
	{
		$this->id = $topicId;
		$this->subscribers = new SplObjectStorage();
	}

	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * Send a message to all the connections in this topic
	 *
	 * @param string|Responses\ControllerResponse $message Payload to publish
	 * @param array $exclude A list of session IDs the message should be excluded from (blacklist)
	 * @param array $eligible A list of session Ids the message should be send to (whitelist)
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	public function broadcast(
		Responses\ControllerResponse|string $message,
		array $exclude = [],
		array $eligible = [],
	): void
	{
		if (!is_string($message) && !$message instanceof Responses\ControllerResponse) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'Provided message for broadcasting have to be string or instance of "%s"',
					Responses\ControllerResponse::class,
				),
			);
		}

		$useEligible = (bool) count($eligible);

		foreach ($this->subscribers as $client) {
			if (in_array($client->getId(), $exclude, true)) {
				continue;
			}

			if ($useEligible && !in_array($client->getParameter('subscribedTopics'), $eligible, true)) {
				continue;
			}

			$client->send(Utils\Json::encode([Controllers\WampApplication::MSG_EVENT, $this->id, (string) $message]));
		}
	}

	public function has(Entities\Client $client): bool
	{
		return $this->subscribers->offsetExists($client);
	}

	public function add(Entities\Client $client): void
	{
		$this->subscribers->offsetSet($client);
	}

	public function remove(Entities\Client $client): void
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

	public function enableAutoDelete(): void
	{
		$this->autoDelete = true;
	}

	public function disableAutoDelete(): void
	{
		$this->autoDelete = false;
	}

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
