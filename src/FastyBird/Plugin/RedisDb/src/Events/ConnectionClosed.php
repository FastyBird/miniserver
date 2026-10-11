<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * After connection closed event
 */
final class ConnectionClosed extends EventDispatcher\Event
{

}
