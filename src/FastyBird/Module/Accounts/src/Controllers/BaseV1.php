<?php declare(strict_types = 1);

/**
 * BaseV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence;
use Exception;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Accounts\Entities as AccountsEntities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Security;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Log;
use RuntimeException;
use stdClass;
use function array_key_exists;
use function assert;
use function in_array;
use function strtolower;
use function strtoupper;
use function strval;

/**
 * API base controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BaseV1
{

	use Nette\SmartObject;

	protected Security\User $user;

	protected Clock\Clock $clock;

	protected Localization\Translator $translator;

	protected Persistence\ManagerRegistry $managerRegistry;

	protected Encoding\Builder $builder;

	protected Router\Validator $routesValidator;

	/** @var Hydrators\Container<PersistenceEntities\CrudEntity> */
	protected Hydrators\Container $hydratorsContainer;

	protected Log\LoggerInterface $logger;

	public function injectUser(Security\User $user): void
	{
		$this->user = $user;
	}

	public function injectClock(Clock\Clock $clock): void
	{
		$this->clock = $clock;
	}

	public function injectTranslator(Localization\Translator $translator): void
	{
		$this->translator = $translator;
	}

	public function injectManagerRegistry(Persistence\ManagerRegistry $managerRegistry): void
	{
		$this->managerRegistry = $managerRegistry;
	}

	public function injectLogger(Log\LoggerInterface|null $logger = null): void
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function injectJsonApiBuilder(Encoding\Builder $builder): void
	{
		$this->builder = $builder;
	}

	public function injectRoutesValidator(Router\Validator $validator): void
	{
		$this->routesValidator = $validator;
	}

	/**
	 * @param Hydrators\Container<PersistenceEntities\CrudEntity> $hydratorsContainer
	 */
	public function injectHydratorsContainer(Hydrators\Container $hydratorsContainer): void
	{
		$this->hydratorsContainer = $hydratorsContainer;
	}

	/**
	 * @throws ApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): ResponseInterface
	{
		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity !== '') {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.relationNotFound.heading')),
				strval($this->translator->translate(
					'//accounts-module.base.messages.relationNotFound.message',
					['relation' => $relationEntity],
				)),
			);
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//accounts-module.base.messages.unknownRelation.heading')),
			strval($this->translator->translate('//accounts-module.base.messages.unknownRelation.message')),
		);
	}

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	protected function createDocument(Message\ServerRequestInterface $request): Encoding\IDocument
	{
		try {
			$data = Utils\Json::decode($request->getBody()->getContents());
			assert($data instanceof stdClass);

			$document = new Encoding\Document($data);

		} catch (Utils\JsonException) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.notValidJson.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notValidJson.message')),
			);
		} catch (CoreExceptions\Runtime) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.notValidJsonApi.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notValidJsonApi.message')),
			);
		}

		return $document;
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 */
	protected function validateIdentifier(
		Message\ServerRequestInterface $request,
		Encoding\IDocument $document,
	): bool
	{
		if (
			in_array(strtoupper($request->getMethod()), [
				RequestMethodInterface::METHOD_POST,
				RequestMethodInterface::METHOD_PATCH,
			], true)
			&& $request->getAttribute(Router\ApiRoutes::URL_ITEM_ID) !== null
			&& $request->getAttribute(Router\ApiRoutes::URL_ITEM_ID) !== $document->getResource()->getId()
		) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.message')),
			);
		}

		return true;
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 */
	protected function validateAccountRelation(
		Utils\ArrayHash $data,
		AccountsEntities\Accounts\Account $account,
		bool $required = false,
	): bool
	{
		if (
			(
				$required && !$data->offsetExists('account')
				|| $data->offsetExists('account')
			) && (
				!$data->offsetGet('account') instanceof AccountsEntities\Accounts\Account
				|| !$account->getId()
					->equals($data->offsetGet('account')
						->getId())
			)
		) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidRelation.message')),
				[
					'pointer' => '/data/relationships/account/data/id',
				],
			);
		}

		return true;
	}

	/**
	 * @throws AccountsExceptions\Runtime
	 */
	protected function getOrmConnection(): Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof Connection) {
			return $connection;
		}

		throw new AccountsExceptions\Runtime('Transformer manager could not be loaded');
	}

	/**
	 * @param PersistenceEntities\CrudEntity|array<PersistenceEntities\CrudEntity>|Query\ResultSet<AccountsEntities\Entity>|null $data
	 *
	 * @throws PersistenceExceptions\Query
	 * @throws Exception
	 */
	protected function buildResponse(
		Message\ServerRequestInterface $request,
		ResponseInterface $response,
		PersistenceEntities\CrudEntity|Query\ResultSet|array|null $data,
	): ResponseInterface
	{
		$totalCount = null;

		if ($data instanceof Query\ResultSet) {
			if (array_key_exists('page', $request->getQueryParams())) {
				$queryParams = $request->getQueryParams();

				$pageOffset = isset($queryParams['page']['offset']) ? (int) $queryParams['page']['offset'] : null;
				$pageLimit = isset($queryParams['page']['limit']) ? (int) $queryParams['page']['limit'] : null;

				$totalCount = $data->getTotalCount();

				if ($data->getTotalCount() > $pageLimit) {
					$data->applyPaging($pageOffset, $pageLimit);
				}
			}
		}

		return $this->builder->build(
			$request,
			$response,
			// @phpstan-ignore-next-line
			$data instanceof Query\ResultSet ? $data->toArray() : $data,
			$totalCount,
			fn (string $link): bool => $this->routesValidator->validate($link),
		);
	}

}
