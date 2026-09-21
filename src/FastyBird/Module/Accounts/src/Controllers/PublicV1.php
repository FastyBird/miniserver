<?php declare(strict_types = 1);

/**
 * PublicV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           23.08.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use Throwable;
use function is_scalar;
use function strval;

/**
 * Account identity controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PublicV1 extends BaseV1
{

	use Controllers\Finders\TIdentity;

	public function __construct(
		protected readonly Models\Entities\Identities\IdentitiesRepository $identitiesRepository,
		private readonly Models\Entities\Accounts\AccountsManager $accountsManager,
		private readonly Helpers\SecurityHash $securityHash,
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 *
	 * @Secured\User(guest)
	 */
	public function register(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// TODO: Registration not implemented yet

		return $response->withStatus(StatusCodeInterface::STATUS_ACCEPTED);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exception
	 *
	 * @Secured\User(guest)
	 */
	public function resetIdentity(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$attributes = $document->getResource()->getAttributes();

		if ($document->getResource()->getType() !== Schemas\Identities\Identity::SCHEMA_TYPE) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidType.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidType.message')),
				[
					'pointer' => '/data/type',
				],
			);
		}

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

		$findQuery = new Queries\Entities\FindIdentities();
		$findQuery->byUid((string) $attributes->get('uid'));

		$identity = $this->identitiesRepository->findOneBy($findQuery);

		if ($identity === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		$account = $identity->getAccount();

		if ($account->isDeleted()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		} elseif ($account->isNotActivated()) {
			$hash = $account->getRequestHash();

			if ($hash === null || !$this->securityHash->isValid($hash)) {
				// Verification hash is expired, create new one for user
				$this->accountsManager->update($account, Utils\ArrayHash::from([
					'requestHash' => $this->securityHash->createKey(),
				]));
			}

			// TODO: Send new user email

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.public.messages.notActivated.heading')),
				strval($this->translator->translate('//accounts-module.public.messages.notActivated.message')),
				[
					'pointer' => '/data/attributes/uid',
				],
			);
		} elseif ($account->isBlocked()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.public.messages.blocked.heading')),
				strval($this->translator->translate('//accounts-module.public.messages.blocked.message')),
				[
					'pointer' => '/data/attributes/uid',
				],
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Update entity
			$this->accountsManager->update($account, Utils\ArrayHash::from([
				'requestHash' => $this->securityHash->createKey(),
			]));

			// TODO: Send reset password email

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
					'type' => 'public-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.public.messages.requestNotSent.heading')),
				strval($this->translator->translate('//accounts-module.public.messages.requestNotSent.message')),
				[
					'pointer' => '/data/attributes/uid',
				],
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $response->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
	}

}
