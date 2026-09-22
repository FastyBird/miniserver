<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use FastyBird\Core\Entities\WebSockets\PushMessages;

/**
 * WebSockets WAMP application interface
 */
interface IWampApplication extends IApplication
{

	public function handlePush(PushMessages\IMessage $message, string $provider): void;

}
