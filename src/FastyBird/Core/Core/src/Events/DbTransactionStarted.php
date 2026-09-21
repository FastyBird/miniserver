<?php declare(strict_types = 1);

/**
 * DbTransactionStarted.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Database transaction started event
 *
 * @package        FastyBird:Tools!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DbTransactionStarted extends EventDispatcher\Event
{

}
