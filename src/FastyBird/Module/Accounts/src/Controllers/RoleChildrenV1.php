<?php declare(strict_types = 1);

/**
 * RoleChildrenV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           03.06.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Persistence\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Queries;
use Psr\Http\Message;
use Ramsey\Uuid\Exception\InvalidArgumentException;

/**
 * Role children API controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class RoleChildrenV1 extends BaseV1
{

	use Controllers\Finders\TRole;

	public function __construct(private readonly SimpleAuthModels\Policies\Repository $policiesRepository)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws InvalidArgumentException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load role
		$role = $this->findRole($request);

		$findQuery = new Queries\Entities\FindRoles();
		$findQuery->forParent($role);

		$children = $this->policiesRepository->getResultSet(
			$findQuery,
			Entities\Roles\Role::class,
		);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $children);
	}

}
