<?php declare(strict_types = 1);

/**
 * Request.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           05.10.21
 */

namespace FastyBird\Core\Events\HttpServer;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * Http request event
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Request extends EventDispatcher\Event
{

	public function __construct(private readonly ServerRequestInterface $request)
	{
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
