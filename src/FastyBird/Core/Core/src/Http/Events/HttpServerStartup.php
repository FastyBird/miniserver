<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * When HTTP web server started
 */
final class HttpServerStartup extends EventDispatcher\Event
{

}
