<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities\Topics;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Entities;
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
			assert($client instanceof Entities\ConnectedClient);
			if (in_array($client->getId(), $exclude, true)) {
				continue;
			}

			if ($useEligible && !in_array($client->getParameter('subscribedTopics'), $eligible, true)) {
				continue;
			}

			$client->send(Utils\Json::encode([Controllers\WampApplication::MSG_EVENT, $this->id, (string) $message]));
		}
	}

	#[Override]
	public function has(Entities\ConnectedClient $client): bool
	{
		return $this->subscribers->offsetExists($client);
	}

	#[Override]
	public function add(Entities\ConnectedClient $client): void
	{
		$this->subscribers->offsetSet($client);
	}

	#[Override]
	public function remove(Entities\ConnectedClient $client): void
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
