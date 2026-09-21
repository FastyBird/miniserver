<?php declare(strict_types = 1);

/**
 * HttpServerRequest.php
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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * HTTP server PSR-7 request event
 *
 * @package        FastyBird:Core!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServerRequest extends EventDispatcher\Event
{

	public function __construct(private readonly ServerRequestInterface $request)
	{
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
