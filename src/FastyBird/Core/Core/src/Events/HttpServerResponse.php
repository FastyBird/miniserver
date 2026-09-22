<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher;

/**
 * HTTP server PSR-7 response event
 */
final class HttpServerResponse extends EventDispatcher\Event
{

	public function __construct(
		private readonly ServerRequestInterface $request,
		private readonly ResponseInterface $response,
	)
	{
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

	public function getResponse(): ResponseInterface
	{
		return $this->response;
	}

}
