<?php declare(strict_types = 1);

namespace FastyBird\Core\EventLoop\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event loop was started event
 */
final class EventLoopStarted extends EventDispatcher\Event
{

}
