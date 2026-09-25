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

use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Models\Policies;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
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

	public function __construct(private readonly Policies\Repository $policiesRepository)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws AccountsExceptions\InvalidState
	 * @throws ApiExceptions\JsonApi
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
