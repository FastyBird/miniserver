<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Basic http response factory
 */
class ResponseFactory implements ResponseFactoryInterface
{

	public function createResponse(
		int $code = StatusCodeInterface::STATUS_OK,
		string $reasonPhrase = '',
	): ResponseInterface
	{
		$stream = Stream::fromResourceUri('php://temp', 'w+b');

		return new Response($code, $stream, [], ['reason' => $reasonPhrase]);
	}

}
