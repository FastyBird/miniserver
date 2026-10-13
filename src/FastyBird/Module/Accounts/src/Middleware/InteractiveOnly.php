<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Middleware;

use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Module\Accounts\Security;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Refuses every route of this module to a request signed in by an API key (an AccountIdentity)
 *
 * An API key is a credential for machine clients: it reads and writes data with its account's
 * roles. This module's routes are account administration (the session and its tokens, the own
 * profile, its password, emails and identities, other accounts, roles, registration), and some
 * of them issue credentials, so none of them is open to a key, with 403. It runs after
 * Security\Middleware\User has signed the request in and before the route's authorization.
 */
final readonly class InteractiveOnly implements MiddlewareInterface
{

	public function __construct(private Security\User $user)
	{
	}

	/**
	 * @throws SecurityExceptions\ForbiddenAccess
	 */
	#[Override]
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->user->getIdentity() instanceof Security\AccountIdentity) {
			throw new SecurityExceptions\ForbiddenAccess('An API key cannot use the accounts module');
		}

		return $handler->handle($request);
	}

}
