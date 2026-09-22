<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event loop is going to be stopped event
 */
final class EventLoopStopping extends EventDispatcher\Event
{

}
