<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Middleware;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function class_exists;
use function count;
use function get_class;
use function is_array;
use function is_object;
use function is_string;

/**
 * Access check middleware
 */
final readonly class Authorization implements MiddlewareInterface
{

	public function __construct(
		private readonly Access\AnnotationChecker $annotationChecker,
	)
	{
	}

	/**
	 * @throws SecurityExceptions\ForbiddenAccess
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	#[Override]
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler,
	): ResponseInterface
	{
		$route = $request->getAttribute(Routing\Router::ROUTE);

		if ($route instanceof Routing\IRoute) {
			$routeCallable = $route->getCallable();

			if (
				is_array($routeCallable)
				&& count($routeCallable) === 2
				&& is_object($routeCallable[0])
				&& is_string($routeCallable[1])
				&& class_exists(get_class($routeCallable[0]))
			) {
				if (!$this->annotationChecker->checkAccess(
					get_class($routeCallable[0]),
					$routeCallable[1],
				)) {
					throw new SecurityExceptions\ForbiddenAccess('Access to this action is not allowed');
				}
			}
		}

		return $handler->handle($request);
	}

}
