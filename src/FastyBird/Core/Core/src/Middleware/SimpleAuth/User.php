<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           09.07.20
 */

namespace FastyBird\Core\Middleware\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\SimpleAuth as SimpleAuthExceptions;
use FastyBird\Core\Services\SimpleAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * User login middleware
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class User implements MiddlewareInterface
{

	public function __construct(private readonly SimpleAuth\Auth $auth)
	{
	}

	/**
	 * @throws SimpleAuthExceptions\Authentication
	 * @throws Exceptions\InvalidState
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 */
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler,
	): ResponseInterface
	{
		$this->auth->login($request);

		return $handler->handle($request);
	}

}
