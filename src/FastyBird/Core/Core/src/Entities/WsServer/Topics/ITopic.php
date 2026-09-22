<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer\Topics;

use Countable;
use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Entities\WsServer as Entities;
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
	 * @param string|Responses\IResponse $message Payload to publish
	 * @param array $exclude A list of session IDs the message should be excluded from (blacklist)
	 * @param array $eligible A list of session Ids the message should be send to (whitelist)
	 */
	public function broadcast(string|Responses\IResponse $message, array $exclude = [], array $eligible = []): void;

	public function has(Entities\IClient $client): bool;

	public function add(Entities\IClient $client): void;

	public function remove(Entities\IClient $client): void;

	public function enableAutoDelete(): void;

	public function disableAutoDelete(): void;

	public function isAutoDeleteEnabled(): bool;

}
