<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Database transaction started event
 */
final class DbTransactionStarted extends EventDispatcher\Event
{

}
