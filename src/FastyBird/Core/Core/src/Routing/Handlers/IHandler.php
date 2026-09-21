<?php declare(strict_types = 1);

/**
 * IHandler.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Routing
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\Core\Routing\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines a contract for invoking a route callable.
 */
interface IHandler
{

	/**
	 * @param array<mixed> $routeArguments
	 */
	public function __invoke(
		callable $callable,
		ServerRequestInterface $request,
		ResponseInterface $response,
		array $routeArguments,
	): ResponseInterface;

}
