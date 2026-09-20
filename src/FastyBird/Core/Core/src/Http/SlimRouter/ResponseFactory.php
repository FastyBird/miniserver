<?php declare(strict_types = 1);

/**
 * ResponseFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Http
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\Core\Http\SlimRouter;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Basic http response factory
 *
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
