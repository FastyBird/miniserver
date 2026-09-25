<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\PushMessages;

use FastyBird\Core\WebSockets\Controllers;
use React\EventLoop;

/**
 * Server push consumer interface
 */
interface IConsumer
{

	public function connect(EventLoop\LoopInterface $loop, Controllers\IWampApplication $application): void;

	public function getName(): string;

	public function close(): void;

}
