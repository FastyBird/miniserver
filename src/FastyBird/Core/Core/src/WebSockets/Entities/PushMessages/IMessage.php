<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities\PushMessages;

/**
 * A push message interface
 */
interface IMessage
{

	public function getTopic(): string;

	public function getData(): array;

}
