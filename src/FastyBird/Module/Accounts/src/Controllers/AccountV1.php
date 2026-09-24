<?php declare(strict_types = 1);

/**
 * AccountV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\Core\Security\SimpleAuth as SimpleAuthSecurity;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message;
use Throwable;
use function strtolower;
use function strval;

/**
 * Account controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class AccountV1 extends BaseV1
{

	public function __construct(
		private readonly Hydrators\Accounts\ProfileAccount $accountHydrator,
		private readonly Models\Entities\Accounts\AccountsManager $accountsManager,
		private readonly SimpleAuthModels\Policies\Repository $policiesRepository,
		private readonly SimpleAuthSecurity\EnforcerFactory $enforcerFactory,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$account = $this->findAccount();

		return $this->buildResponse($request, $response, $account);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$account = $this->findAccount();

		$document = $this->createDocument($request);

		if ($account->getId()->toString() !== $document->getResource()->getId()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.message')),
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Accounts\Account::SCHEMA_TYPE) {
				$account = $this->accountsManager->update(
					$account,
					$this->accountHydrator->hydrate($document, $account),
				);

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
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'account-controller',
					'exception' => Logging\Logger::buildException($ex),
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
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->findAccount();

		// TODO: Closing account not implemented yet

		return $response->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$account = $this->findAccount();

		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_EMAILS) {
			return $this->buildResponse($request, $response, $account->getEmails());
		} elseif ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES) {
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

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	private function findAccount(): Entities\Accounts\Account
	{
		if ($this->user->getAccount() === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				strval($this->translator->translate('//accounts-module.base.messages.forbidden.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.forbidden.message')),
			);
		}

		return $this->user->getAccount();
	}

}
