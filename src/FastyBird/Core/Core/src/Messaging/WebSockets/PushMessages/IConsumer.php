<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

use FastyBird\Core\Controllers\WebSockets\IWampApplication;
use React\EventLoop;

/**
 * Server push consumer interface
 */
interface IConsumer
{

	public function connect(EventLoop\LoopInterface $loop, IWampApplication $application): void;

	public function getName(): string;

	public function close(): void;

}
