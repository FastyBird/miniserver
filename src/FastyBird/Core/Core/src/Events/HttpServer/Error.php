<?php declare(strict_types = 1);

/**
 * Error.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Core\Events\HttpServer;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Connection error event
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Error extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable $ex)
	{
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

}
