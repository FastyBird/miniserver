<?php declare(strict_types = 1);

/**
 * AccountsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           21.06.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Casbin\Exceptions as CasbinExceptions;
use Doctrine;
use Exception;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Types;
use FastyBird\Module\Accounts\Utilities;
use FastyBird\SimpleAuth\Exceptions as SimpleAuthExceptions;
use FastyBird\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use IPub\JsonAPIDocument;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function array_filter;
use function array_map;
use function count;
use function end;
use function explode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function strval;

/**
 * Accounts controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 * @Secured\Role(manager,administrator)
 */
final class AccountsV1 extends BaseV1
{

	public function __construct(
		private readonly Hydrators\Accounts\Account $accountHydrator,
		private readonly Models\Entities\Accounts\AccountsRepository $accountsRepository,
		private readonly Models\Entities\Accounts\AccountsManager $accountsManager,
		private readonly Models\Entities\Identities\IdentitiesManager $identitiesManager,
		private readonly SimpleAuthModels\Policies\Repository $policiesRepository,
		private readonly SimpleAuthSecurity\EnforcerFactory $enforcerFactory,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidState
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindAccounts();

		$accounts = $this->accountsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $accounts);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Find account
		$account = $this->findAccount($request);

		return $this->buildResponse($request, $response, $account);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Accounts\Account::SCHEMA_TYPE) {
				$createData = $this->accountHydrator->hydrate($document);

				// Store item into database
				$account = $this->accountsManager->create($createData);

				$this->assignAccountToRoles($document, $account);
			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.invalidType.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.invalidType.message')),
					[
						'pointer' => '/data/type',
					],
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Exceptions\AccountRoleInvalid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.message')),
				[
					'pointer' => '/data/relationships/roles/data/id',
				],
			);
		} catch (DoctrineCrudExceptions\EntityCreation $ex) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/' . $ex->getField(),
				],
			);
		} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
			if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.message')),
					[
						'pointer' => '/data/id',
					],
				);
			} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
				$columnParts = explode('.', $match['key']);
				$columnKey = end($columnParts);

				if (str_starts_with($columnKey, 'account_')) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading')),
						strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message')),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
								Utils\Strings::substring($columnKey, 8),
							),
						],
					);
				}
			}

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message')),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'account-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.notCreated.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notCreated.message')),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$response = $this->buildResponse($request, $response, $account);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$account = $this->findAccount($request);

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Accounts\Account::SCHEMA_TYPE) {
				$updateData = $this->accountHydrator->hydrate($document, $account);

				$account = $this->accountsManager->update($account, $updateData);

				$this->assignAccountToRoles($document, $account);
			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.invalidType.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.invalidType.message')),
					[
						'pointer' => '/data/type',
					],
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Exceptions\AccountRoleInvalid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.message')),
				[
					'pointer' => '/data/relationships/roles/data/id',
				],
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'account-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.notUpdated.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notUpdated.message')),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $this->buildResponse($request, $response, $account);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$account = $this->findAccount($request);

		if (
			$this->user->getAccount() !== null
			&& $account->getId()->equals($this->user->getAccount()->getId())
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.accounts.messages.selfNotDeletable.heading')),
				strval($this->translator->translate('//accounts-module.accounts.messages.selfNotDeletable.message')),
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$updateData = Utils\ArrayHash::from([
				'state' => Types\AccountState::DELETED,
			]);

			$this->accountsManager->update($account, $updateData);

			foreach ($account->getIdentities() as $identity) {
				$updateIdentity = Utils\ArrayHash::from([
					'state' => Types\IdentityState::DELETED,
				]);

				$this->identitiesManager->update($identity, $updateIdentity);
			}

			$this->enforcerFactory->getEnforcer()->deleteUser($account->getId()->toString());
			$this->enforcerFactory->getEnforcer()->invalidateCache();

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'account-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.notDeleted.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notDeleted.message')),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $response->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load account
		$account = $this->findAccount($request);

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES) {
			return $this->buildResponse($request, $response, $account->getIdentities());
		} elseif ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_ROLES) {
			$roles = $this->enforcerFactory->getEnforcer()->getRolesForUser($account->getId()->toString());

			$policies = [];

			foreach ($roles as $role) {
				$findPoliciesQuery = new Queries\Entities\FindRoles();
				$findPoliciesQuery->byName($role);

				$policy = $this->policiesRepository->findOneBy(
					$findPoliciesQuery,
					Entities\Roles\Role::class,
				);

				if ($policy !== null) {
					$policies[] = $policy;
				}
			}

			return $this->buildResponse($request, $response, $policies);
		}

		if ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_EMAILS) {
			return $this->buildResponse($request, $response, $account->getEmails());
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findAccount(
		Message\ServerRequestInterface $request,
	): Entities\Accounts\Account
	{
		if (!Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		$findQuery = new Queries\Entities\FindAccounts();
		$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

		$account = $this->accountsRepository->findOneBy($findQuery);

		if ($account === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		return $account;
	}

	/**
	 * @throws CasbinExceptions\CasbinException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\AccountRoleInvalid
	 * @throws SimpleAuthExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function assignAccountToRoles(JsonAPIDocument\IDocument $document, Entities\Accounts\Account $account): void
	{
		$relationships = $document->getResource()->getRelationships();

		$assignRoles = [];

		$hasRoleRelation = false;

		foreach ($relationships->getAll() as $relationship) {
			if ($relationship->getData() instanceof JsonAPIDocument\Objects\IResourceIdentifierCollection) {
				foreach ($relationship->getData()->getAll() as $resource) {
					if ($resource->getType() === Schemas\Roles\Role::SCHEMA_TYPE && is_string($resource->getId())) {
						$hasRoleRelation = true;

						$findRolesQuery = new Queries\Entities\FindRoles();
						$findRolesQuery->byId(Uuid\Uuid::fromString($resource->getId()));

						$assignRoles[] = $this->policiesRepository->findOneBy(
							$findRolesQuery,
							Entities\Roles\Role::class,
						);
					}
				}
			}
		}

		if ($hasRoleRelation) {
			$assignRoles = array_filter(
				$assignRoles,
				static fn (Entities\Roles\Role|null $role) => $role !== null,
			);

			foreach ($assignRoles as $assignRole) {
				/**
				 * Special roles like administrator or user
				 * can not be assigned to account with other roles
				 */
				if (
					in_array($assignRole->getName(), Accounts\Constants::SINGLE_ROLES, true)
					&& count($assignRoles) > 1
				) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be combined with other roles', $assignRole->getName()),
					);
				}

				/**
				 * Special roles like visitor or guest
				 * can not be assigned to account
				 */
				if (in_array($assignRole->getName(), Accounts\Constants::NOT_ASSIGNABLE_ROLES, true)) {
					throw new Exceptions\AccountRoleInvalid(
						sprintf('Role %s could not be assigned to account', $assignRole->getName()),
					);
				}
			}

			$this->enforcerFactory->getEnforcer()->deleteRolesForUser($account->getId()->toString());
			$this->enforcerFactory->getEnforcer()->addRolesForUser(
				$account->getId()->toString(),
				array_map(static fn (Entities\Roles\Role $role): string => $role->getName(), $assignRoles),
			);

			$this->enforcerFactory->getEnforcer()->invalidateCache();
		}
	}

}
