<?php declare(strict_types = 1);

/**
 * ITopic.php
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

use Countable;
use FastyBird\Library\WebSockets\Application\Responses;
use FastyBird\Library\WebSockets\Entities;
use IteratorAggregate;

/**
 * A topic/channel containing connections that have subscribed to it
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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

	public function has(Entities\Clients\IClient $client): bool;

	public function add(Entities\Clients\IClient $client): void;

	public function remove(Entities\Clients\IClient $client): void;

	public function enableAutoDelete(): void;

	public function disableAutoDelete(): void;

	public function isAutoDeleteEnabled(): bool;

}
