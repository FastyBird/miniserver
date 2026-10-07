<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Commands\WsServer before it creates the socket. It is the only start event
 * production dispatches: nothing there calls Server\ServerRuntime::run().
 */
final class ServerLaunched extends EventDispatcher\Event
{

}
