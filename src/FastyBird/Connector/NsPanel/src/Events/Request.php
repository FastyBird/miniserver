<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Events;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * Http request event
 */
class Request extends EventDispatcher\Event
{

	public function __construct(
		private readonly ServerRequestInterface $request,
	)
	{
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
