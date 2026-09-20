<?php declare(strict_types = 1);

/**
 * SessionV1.php
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

use DateTimeImmutable;
use Doctrine;
use Exception;
use FastyBird\Core\Constants\SimpleAuth;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions\SimpleAuth as SimpleAuthExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Persistence\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\Core\Persistence\SimpleAuth\Queries as SimpleAuthQueries;
use FastyBird\Core\Security\SimpleAuth as SimpleAuthSecurity;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Types\SimpleAuth as SimpleAuthTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Security;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_scalar;
use function strtolower;
use function strval;

/**
 * User session controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SessionV1 extends BaseV1
{

	public function __construct(
		private readonly SimpleAuthModels\Tokens\Repository $tokensRepository,
		private readonly SimpleAuthModels\Tokens\Manager $tokensManager,
		private readonly SimpleAuthSecurity\TokenReader $tokenReader,
		private readonly SimpleAuthSecurity\TokenBuilder $tokenBuilder,
		private readonly SimpleAuthSecurity\EnforcerFactory $enforcerFactory,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 *
	 * @Secured\User(loggedIn)
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$accessToken = $this->getToken($request);

		return $this->buildResponse($request, $response, $accessToken);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured\User(guest)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$attributes = $document->getResource()->getAttributes();

		if (!$attributes->has('uid') || !is_scalar($attributes->get('uid'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/uid',
				],
			);
		}

		if (!$attributes->has('password') || !is_scalar($attributes->get('password'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/password',
				],
			);
		}

		try {
			// Login user with system authenticator
			$this->user->login((string) $attributes->get('uid'), (string) $attributes->get('password'));

		} catch (Throwable $ex) {
			if ($ex instanceof Exceptions\AccountNotFound) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.session.messages.unknownAccount.heading')),
					strval($this->translator->translate('//accounts-module.session.messages.unknownAccount.message')),
				);
			} elseif ($ex instanceof Exceptions\AuthenticationFailed) {
				throw match ($ex->getCode()) {
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					Security\Authenticator::ACCOUNT_PROFILE_BLOCKED, Security\Authenticator::ACCOUNT_PROFILE_DELETED => new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_FORBIDDEN,
						strval($this->translator->translate('//accounts-module.base.messages.forbidden.heading')),
						strval($this->translator->translate('//accounts-module.base.messages.forbidden.message')),
					),
					default => new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval(
							$this->translator->translate('//accounts-module.session.messages.unknownAccount.heading'),
						),
						strval(
							$this->translator->translate('//accounts-module.session.messages.unknownAccount.message'),
						),
					),
				};
			} else {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
						'type' => 'session-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.notCreated.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.notCreated.message')),
				);
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$validTill = $this->getNow()->modify(Entities\Tokens\AccessToken::TOKEN_EXPIRATION);

			$values = Utils\ArrayHash::from([
				'id' => Uuid\Uuid::uuid4(),
				'entity' => Entities\Tokens\AccessToken::class,
				'token' => $this->createToken(
					$this->user->getId() ?? Uuid\Uuid::uuid4(),
					$this->enforcerFactory->getEnforcer()->getRolesForUser(
						$this->user->getId()?->toString() ?? SimpleAuth\Constants::USER_ANONYMOUS,
					),
					$validTill,
				),
				'validTill' => $validTill,
				'state' => SimpleAuthTypes\TokenState::ACTIVE,
				'identity' => $this->user->getIdentity(),
			]);

			$accessToken = $this->tokensManager->create($values);

			$validTill = $this->getNow()->modify(Entities\Tokens\RefreshToken::TOKEN_EXPIRATION);

			$values = Utils\ArrayHash::from([
				'id' => Uuid\Uuid::uuid4(),
				'entity' => Entities\Tokens\RefreshToken::class,
				'accessToken' => $accessToken,
				'token' => $this->createToken($this->user->getId() ?? Uuid\Uuid::uuid4(), [], $validTill),
				'validTill' => $validTill,
				'state' => SimpleAuthTypes\TokenState::ACTIVE,
			]);

			$this->tokensManager->create($values);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'session-controller',
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

		$response = $this->buildResponse($request, $response, $accessToken);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured\User(guest)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$attributes = $document->getResource()->getAttributes();

		if (!$attributes->has('refresh') || !is_scalar($attributes->get('refresh'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/refresh',
				],
			);
		}

		$refreshToken = $this->tokensRepository->findOneByToken(
			(string) $attributes->get('refresh'),
			Entities\Tokens\RefreshToken::class,
		);

		if ($refreshToken === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.session.messages.invalidRefreshToken.heading')),
				strval($this->translator->translate('//accounts-module.session.messages.invalidRefreshToken.message')),
				[
					'pointer' => '/data/attributes/refresh',
				],
			);
		}

		if (
			$refreshToken->getValidTill() !== null
			&& $refreshToken->getValidTill() < $this->getNow()
		) {
			// Remove expired tokens
			$this->tokensManager->delete($refreshToken->getAccessToken());

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.session.messages.refreshTokenExpired.heading')),
				strval($this->translator->translate('//accounts-module.session.messages.refreshTokenExpired.message')),
				[
					'pointer' => '/data/attributes/refresh',
				],
			);
		}

		$accessToken = $refreshToken->getAccessToken();

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Auto-login user
			$this->user->login($accessToken->getIdentity());

			$validTill = $this->getNow()->modify(Entities\Tokens\AccessToken::TOKEN_EXPIRATION);

			$values = Utils\ArrayHash::from([
				'id' => Uuid\Uuid::uuid4(),
				'entity' => Entities\Tokens\AccessToken::class,
				'token' => $this->createToken(
					$this->user->getId() ?? Uuid\Uuid::uuid4(),
					$this->enforcerFactory->getEnforcer()->getRolesForUser(
						$this->user->getId()?->toString() ?? SimpleAuth\Constants::USER_ANONYMOUS,
					),
					$validTill,
				),
				'validTill' => $validTill,
				'state' => SimpleAuthTypes\TokenState::ACTIVE,
				'identity' => $this->user->getIdentity(),
			]);

			$newAccessToken = $this->tokensManager->create($values);

			$validTill = $this->getNow()->modify(Entities\Tokens\RefreshToken::TOKEN_EXPIRATION);

			$values = Utils\ArrayHash::from([
				'id' => Uuid\Uuid::uuid4(),
				'entity' => Entities\Tokens\RefreshToken::class,
				'accessToken' => $newAccessToken,
				'token' => $this->createToken($this->user->getId() ?? Uuid\Uuid::uuid4(), [], $validTill),
				'validTill' => $validTill,
				'state' => SimpleAuthTypes\TokenState::ACTIVE,
			]);

			$this->tokensManager->create($values);

			$this->tokensManager->delete($refreshToken);
			$this->tokensManager->delete($accessToken);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'session-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval(
					$this->translator->translate('//accounts-module.session.messages.refreshingTokenFailed.heading'),
				),
				strval(
					$this->translator->translate('//accounts-module.session.messages.refreshingTokenFailed.message'),
				),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$response = $this->buildResponse($request, $response, $newAccessToken);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 *
	 * @Secured\User(loggedIn)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$accessToken = $this->getToken($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($accessToken->getRefreshToken() !== null) {
				$this->tokensManager->delete($accessToken->getRefreshToken());
			}

			$this->tokensManager->delete($accessToken);

			$this->user->logout();

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS->value,
					'type' => 'session-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval(
					$this->translator->translate('//accounts-module.session.messages.destroyingSessionFailed.heading'),
				),
				strval(
					$this->translator->translate('//accounts-module.session.messages.destroyingSessionFailed.message'),
				),
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
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured\User(loggedIn)
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT) {
			return $this->buildResponse($request, $response, $this->user->getAccount());
		}

		return parent::readRelationship($request, $response);
	}

	private function getNow(): DateTimeImmutable
	{
		$now = $this->clock->getNow();
		assert($now instanceof DateTimeImmutable);

		return $now;
	}

	/**
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	private function getToken(Message\ServerRequestInterface $request): Entities\Tokens\AccessToken
	{
		$token = $this->tokenReader->read($request);

		if ($token === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				strval($this->translator->translate('//accounts-module.base.messages.forbidden.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.forbidden.message')),
			);
		}

		$findToken = new SimpleAuthQueries\FindTokens();
		$findToken->byToken($token->toString());

		$accessToken = $this->tokensRepository->findOneBy($findToken, Entities\Tokens\AccessToken::class);

		if (
			$this->user->getAccount() !== null
			&& $accessToken instanceof Entities\Tokens\AccessToken
			&& $accessToken->getIdentity()
				->getAccount()
				->getId()
				->equals($this->user->getAccount()->getId())
		) {
			return $accessToken;
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_FORBIDDEN,
			strval($this->translator->translate('//accounts-module.base.messages.forbidden.heading')),
			strval($this->translator->translate('//accounts-module.base.messages.forbidden.message')),
		);
	}

	/**
	 * @param array<string> $roles
	 */
	private function createToken(
		Uuid\UuidInterface $userId,
		array $roles,
		DateTimeImmutable|null $validTill,
	): string
	{
		return $this->tokenBuilder->build($userId->toString(), $roles, $validTill)->toString();
	}

}
