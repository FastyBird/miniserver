<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * HTTP server PSR-7 request event
 */
final class HttpServerRequest extends EventDispatcher\Event
{

	public function __construct(private readonly ServerRequestInterface $request)
	{
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
