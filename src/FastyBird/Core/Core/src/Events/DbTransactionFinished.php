<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Database transaction finished event
 */
class DbTransactionFinished extends EventDispatcher\Event
{

}
