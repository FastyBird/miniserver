<?php declare(strict_types = 1);

namespace FastyBird\Core\Middleware\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as SimpleAuthExceptions;
use FastyBird\Core\Services\SimpleAuth;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * User login middleware
 */
final readonly class User implements MiddlewareInterface
{

	public function __construct(private readonly SimpleAuth\Auth $auth)
	{
	}

	/**
	 * @throws SimpleAuthExceptions\Authentication
	 * @throws Exceptions\InvalidState
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 */
	#[Override]
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler,
	): ResponseInterface
	{
		$this->auth->login($request);

		return $handler->handle($request);
	}

}
