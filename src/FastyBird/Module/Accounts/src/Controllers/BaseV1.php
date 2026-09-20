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
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Entities\DoctrineCrud;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Persistence\DoctrineOrmQuery\ResultSet;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi as JsonApiBuilder;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Persistence\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
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

	protected DateTimeFactory\Clock $clock;

	protected Localization\Translator $translator;

	protected Persistence\ManagerRegistry $managerRegistry;

	protected JsonApiBuilder\Builder $builder;

	protected Router\Validator $routesValidator;

	/** @var JsonApiHydrators\Container<DoctrineCrud\IEntity> */
	protected JsonApiHydrators\Container $hydratorsContainer;

	protected Log\LoggerInterface $logger;

	public function injectUser(Security\User $user): void
	{
		$this->user = $user;
	}

	public function injectClock(DateTimeFactory\Clock $clock): void
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

	public function injectJsonApiBuilder(JsonApiBuilder\Builder $builder): void
	{
		$this->builder = $builder;
	}

	public function injectRoutesValidator(Router\Validator $validator): void
	{
		$this->routesValidator = $validator;
	}

	/**
	 * @param JsonApiHydrators\Container<DoctrineCrud\IEntity> $hydratorsContainer
	 */
	public function injectHydratorsContainer(JsonApiHydrators\Container $hydratorsContainer): void
	{
		$this->hydratorsContainer = $hydratorsContainer;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): ResponseInterface
	{
		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity !== '') {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.relationNotFound.heading')),
				strval($this->translator->translate(
					'//accounts-module.base.messages.relationNotFound.message',
					['relation' => $relationEntity],
				)),
			);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//accounts-module.base.messages.unknownRelation.heading')),
			strval($this->translator->translate('//accounts-module.base.messages.unknownRelation.message')),
		);
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	protected function createDocument(Message\ServerRequestInterface $request): JsonApi\IDocument
	{
		try {
			$data = Utils\Json::decode($request->getBody()->getContents());
			assert($data instanceof stdClass);

			$document = new JsonApi\Document($data);

		} catch (Utils\JsonException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.notValidJson.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notValidJson.message')),
			);
		} catch (JsonApi\Exceptions\Runtime) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.notValidJsonApi.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notValidJsonApi.message')),
			);
		}

		return $document;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function validateIdentifier(
		Message\ServerRequestInterface $request,
		JsonApi\IDocument $document,
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
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidIdentifier.message')),
			);
		}

		return true;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function validateAccountRelation(
		Utils\ArrayHash $data,
		Entities\Accounts\Account $account,
		bool $required = false,
	): bool
	{
		if (
			(
				$required && !$data->offsetExists('account')
				|| $data->offsetExists('account')
			) && (
				!$data->offsetGet('account') instanceof Entities\Accounts\Account
				|| !$account->getId()
					->equals($data->offsetGet('account')
						->getId())
			)
		) {
			throw new JsonApiExceptions\JsonApiError(
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
	 * @throws Exceptions\Runtime
	 */
	protected function getOrmConnection(): Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

	/**
	 * @param DoctrineCrud\IEntity|array<DoctrineCrud\IEntity>|ResultSet<Entities\Entity>|null $data
	 *
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exception
	 */
	protected function buildResponse(
		Message\ServerRequestInterface $request,
		ResponseInterface $response,
		DoctrineCrud\IEntity|ResultSet|array|null $data,
	): ResponseInterface
	{
		$totalCount = null;

		if ($data instanceof ResultSet) {
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
			$data instanceof ResultSet ? $data->toArray() : $data,
			$totalCount,
			fn (string $link): bool => $this->routesValidator->validate($link),
		);
	}

}
