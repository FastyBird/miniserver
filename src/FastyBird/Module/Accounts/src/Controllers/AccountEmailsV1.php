<?php declare(strict_types = 1);

/**
 * AccountEmailsV1.php
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
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Utilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use RuntimeException;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strtolower;
use function strval;

/**
 * Emails controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class AccountEmailsV1 extends BaseV1
{

	use Controllers\Finders\TEmail;

	public function __construct(
		private readonly Hydrators\Emails\ProfileEmail $emailHydrator,
		protected readonly Models\Entities\Emails\EmailsRepository $emailsRepository,
		private readonly Models\Entities\Emails\EmailsManager $emailsManager,
		private readonly Helpers\SecurityHash $securityHash,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindEmails();
		$findQuery->forAccount($this->findAccount());

		$emails = $this->emailsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $emails);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Find email
		$email = $this->findEmail($request, $this->findAccount());

		return $this->buildResponse($request, $response, $email);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Get user profile account or url defined account
		$account = $this->findAccount();

		$document = $this->createDocument($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Emails\Email::SCHEMA_TYPE) {
				$createData = $this->emailHydrator->hydrate($document);
				$createData->offsetSet('account', $account);
				$createData->offsetSet('verificationHash', $this->securityHash->createKey());
				$createData->offsetSet('verificationCreated', $this->clock->getNow());

				// Store item into database
				$email = $this->emailsManager->create($createData);

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

		} catch (Exceptions\EmailIsNotValid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/address',
				],
			);
		} catch (Exceptions\EmailAlreadyTaken) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.emails.messages.taken.heading')),
				strval($this->translator->translate('//accounts-module.emails.messages.taken.message')),
				[
					'pointer' => '/data/attributes/address',
				],
			);
		} catch (DoctrineCrudExceptions\EntityCreation $ex) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
				],
			);
		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Doctrine\ORM\Exception\EntityIdentityCollisionException) {
			// ORM 3 detects a client-supplied duplicate id while adding to the identity
			// map, which happens before the INSERT that used to surface this as a DBAL
			// unique constraint violation on PRIMARY. Same condition, reported earlier.
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.message')),
				[
					'pointer' => '/data/id',
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

				if (str_starts_with($columnKey, 'email_')) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading')),
						strval($this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message')),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
								Utils\Strings::substring($columnKey, 6),
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
					'type' => 'account-email-controller',
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

		$response = $this->buildResponse($request, $response, $email);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$email = $this->findEmail($request, $this->findAccount());

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Emails\Email::SCHEMA_TYPE) {
				$updateEmailData = $this->emailHydrator->hydrate($document, $email);

				$email = $this->emailsManager->update($email, $updateEmailData);

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
					'type' => 'account-email-controller',
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

		return $this->buildResponse($request, $response, $email);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$email = $this->findEmail($request, $this->findAccount());

		if ($email->isDefault()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.emails.messages.defaultNotDeletable.heading')),
				strval($this->translator->translate('//accounts-module.emails.messages.defaultNotDeletable.message')),
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->emailsManager->delete($email);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'account-email-controller',
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
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load email
		$email = $this->findEmail($request, $this->findAccount());

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT) {
			return $this->buildResponse($request, $response, $email->getAccount());
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
