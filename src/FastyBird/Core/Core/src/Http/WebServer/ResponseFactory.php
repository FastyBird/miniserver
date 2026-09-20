<?php declare(strict_types = 1);

/**
 * ResponseFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 * @since          1.0.0
 *
 * @date           17.03.20
 */

namespace FastyBird\Core\Http\WebServer;

use FastyBird\Core\Http\SlimRouter\Stream;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extended HTTP response factory
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
