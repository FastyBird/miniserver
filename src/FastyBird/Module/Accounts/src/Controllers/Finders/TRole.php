<?php declare(strict_types = 1);

/**
 * TRole.php
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

namespace FastyBird\Module\Accounts\Controllers\Finders;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\SimpleAuth\Models as SimpleAuthModels;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette\Localization;
use Psr\Http\Message;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\ITranslator $translator
 * @property-read SimpleAuthModels\Policies\Repository $policiesRepository
 */
trait TRole
{

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findRole(
		Message\ServerRequestInterface $request,
	): Entities\Roles\Role
	{
		if (!Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		$findQuery = new Queries\Entities\FindRoles();
		$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

		$role = $this->policiesRepository->findOneBy($findQuery, Entities\Roles\Role::class);

		if ($role === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		return $role;
	}

}
