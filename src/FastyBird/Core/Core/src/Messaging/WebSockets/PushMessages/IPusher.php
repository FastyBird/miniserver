<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

/**
 * Server message pusher interface
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
