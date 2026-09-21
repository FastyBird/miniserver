<?php declare(strict_types = 1);

/**
 * EventLoopStopped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           19.01.24
 */

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event loop was stopped event
 *
 * @package        FastyBird:Application!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EventLoopStopped extends EventDispatcher\Event
{

}
