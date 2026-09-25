<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities\Topics;

use Countable;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Entities;
use IteratorAggregate;

/**
 * A topic/channel containing connections that have subscribed to it
 */
interface ITopic extends IteratorAggregate, Countable
{

	public function getId(): string;

	/**
	 * Send a message to all the connections in this topic
	 *
	 * @param string|Responses\ControllerResponse $message Payload to publish
	 * @param array $exclude A list of session IDs the message should be excluded from (blacklist)
	 * @param array $eligible A list of session Ids the message should be send to (whitelist)
	 */
	public function broadcast(
		string|Responses\ControllerResponse $message,
		array $exclude = [],
		array $eligible = [],
	): void;

	public function has(Entities\ConnectedClient $client): bool;

	public function add(Entities\ConnectedClient $client): void;

	public function remove(Entities\ConnectedClient $client): void;

	public function enableAutoDelete(): void;

	public function disableAutoDelete(): void;

	public function isAutoDeleteEnabled(): bool;

}
