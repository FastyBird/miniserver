<?php declare(strict_types = 1);

/**
 * HttpServerStartup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           05.10.21
 */

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * When HTTP web server started
 *
 * @package        FastyBird:Core!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServerStartup extends EventDispatcher\Event
{

}
