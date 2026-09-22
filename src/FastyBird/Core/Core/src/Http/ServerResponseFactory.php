<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use FastyBird\Core\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extended HTTP response factory
 */
class ServerResponseFactory implements ResponseFactoryInterface
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function createResponse(
		int $code = StatusCodeInterface::STATUS_OK,
		string $reasonPhrase = '',
	): ResponseInterface
	{
		$stream = Stream::fromResourceUri('php://temp', 'w+b');

		return new ServerResponse($code, $stream, [], ['reason' => $reasonPhrase]);
	}

}
