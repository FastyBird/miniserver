<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

use FastyBird\Core\WebSockets\Entities\PushMessages;

/**
 * WebSockets WAMP application interface
 */
interface IWampApplication extends Dispatcher
{

	public function handlePush(PushMessages\IMessage $message, string $provider): void;

}
