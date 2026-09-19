<?php declare(strict_types = 1);

/**
 * Topic.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.02.17
 */

namespace FastyBird\Library\WebSockets\Wamp\Entities\Topics;

use FastyBird\Library\WebSockets\Application as WebSocketsApplication;
use FastyBird\Library\WebSockets\Entities as WebSocketsEntities;
use FastyBird\Library\WebSockets\Wamp\Application;
use FastyBird\Library\WebSockets\Wamp\Exceptions;
use Nette;
use Nette\Utils;
use SplObjectStorage;
use Traversable;
use function assert;
use function count;
use function in_array;
use function is_string;
use function sprintf;

/**
 * A topic/channel containing connections that have subscribed to it
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
	public function broadcast(
		WebSocketsApplication\Responses\IResponse|string $message,
		array $exclude = [],
		array $eligible = [],
	): void
	{
		if (!is_string($message) && !$message instanceof WebSocketsApplication\Responses\IResponse) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'Provided message for broadcasting have to be string or instance of "%s"',
					WebSocketsApplication\Responses\IResponse::class,
				),
			);
		}

		$useEligible = (bool) count($eligible);

		foreach ($this->subscribers as $client) {
			assert($client instanceof WebSocketsEntities\Clients\IClient);
			if (in_array($client->getId(), $exclude, true)) {
				continue;
			}

			if ($useEligible && !in_array($client->getParameter('subscribedTopics'), $eligible, true)) {
				continue;
			}

			$client->send(Utils\Json::encode([Application\Application::MSG_EVENT, $this->id, (string) $message]));
		}
	}

	public function has(WebSocketsEntities\Clients\IClient $client): bool
	{
		return $this->subscribers->offsetExists($client);
	}

	public function add(WebSocketsEntities\Clients\IClient $client): void
	{
		$this->subscribers->offsetSet($client);
	}

	public function remove(WebSocketsEntities\Clients\IClient $client): void
	{
		if ($this->subscribers->offsetExists($client)) {
			$this->subscribers->offsetUnset($client);
		}
	}

	public function getIterator(): Traversable
	{
		return $this->subscribers;
	}

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
	public function __toString()
	{
		return $this->getId();
	}

}
