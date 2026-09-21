<?php declare(strict_types = 1);

/**
 * HttpServerError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * HTTP server connection error event
 *
 * @package        FastyBird:Core!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServerError extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable $ex)
	{
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

}
