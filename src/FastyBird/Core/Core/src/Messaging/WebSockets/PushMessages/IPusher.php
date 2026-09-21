<?php declare(strict_types = 1);

/**
 * IPusher.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

/**
 * Server message pusher interface
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IPusher
{

	/**
	 * @param array<array> $routeParameters
	 */
	public function push(
		array|string $data,
		string $destination,
		array $routeParameters = [],
		array $context = [],
	): void;

	public function setConnected(bool $bool = true): void;

	public function isConnected(): bool;

	public function getName(): string;

}
