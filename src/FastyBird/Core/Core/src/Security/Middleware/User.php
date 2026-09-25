<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Middleware;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Services;
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

	public function __construct(private readonly Services\Auth $auth)
	{
	}

	/**
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidState
	 * @throws SecurityExceptions\UnauthorizedAccess
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
