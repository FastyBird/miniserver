<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Middleware;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Services;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid;

/**
 * User login middleware
 */
final readonly class User implements MiddlewareInterface
{

	public function __construct(private readonly Services\Auth $auth)
	{
	}

	/**
	 * Signs the request's user in: by the bearer token, or, while API keys are enabled and only
	 * for a request without a bearer token, by the X-Api-Key header (Services\Auth)
	 *
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws SecurityExceptions\UnauthorizedAccess
	 * @throws Uuid\Exception\InvalidArgumentException
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
