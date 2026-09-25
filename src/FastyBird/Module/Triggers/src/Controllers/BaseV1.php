<?php declare(strict_types = 1);

/**
 * BaseV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Triggers\Controllers;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence;
use Exception;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Query;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Router;
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
use function in_array;
use function is_array;
use function strtoupper;
use function strval;

/**
 * API base controller
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BaseV1
{

	use Nette\SmartObject;

	protected Localization\Translator $translator;

	protected Persistence\ManagerRegistry $managerRegistry;

	protected Encoding\Builder $builder;

	protected Router\Validator $routesValidator;

	/** @var Hydrators\Container<Entities\CrudEntity> */
	protected Hydrators\Container $hydratorsContainer;

	protected Log\LoggerInterface $logger;

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
	 * @param Hydrators\Container<Entities\CrudEntity> $hydratorsContainer
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
		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity !== '') {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.relationNotFound.heading')),
				strval($this->translator->translate(
					'//triggers-module.base.messages.relationNotFound.message',
					['relation' => $relationEntity],
				)),
			);
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//triggers-module.base.messages.unknownRelation.heading')),
			strval($this->translator->translate('//triggers-module.base.messages.unknownRelation.message')),
		);
	}

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	protected function createDocument(Message\ServerRequestInterface $request): Encoding\IDocument
	{
		try {
			$content = Utils\Json::decode($request->getBody()->getContents());

			if (!$content instanceof stdClass) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_BAD_REQUEST,
					strval($this->translator->translate('//triggers-module.base.messages.notValidJsonApi.heading')),
					strval($this->translator->translate('//triggers-module.base.messages.notValidJsonApi.message')),
				);
			}

			$document = new Encoding\Document($content);

		} catch (Utils\JsonException) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//triggers-module.base.messages.notValidJson.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notValidJson.message')),
			);
		} catch (ApplicationExceptions\Runtime) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//triggers-module.base.messages.notValidJsonApi.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notValidJsonApi.message')),
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
				strval($this->translator->translate('//triggers-module.base.messages.invalidIdentifier.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.invalidIdentifier.message')),
			);
		}

		return true;
	}

	/**
	 * @throws TriggersExceptions\Runtime
	 */
	protected function getOrmConnection(): Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof Connection) {
			return $connection;
		}

		throw new TriggersExceptions\Runtime('Entity manager could not be loaded');
	}

	/**
	 * @param Entities\CrudEntity|Query\ResultSet<Entities\CrudEntity>|array<Entities\CrudEntity> $data
	 *
	 * @throws Exception
	 */
	protected function buildResponse(
		Message\ServerRequestInterface $request,
		ResponseInterface $response,
		Entities\CrudEntity|Query\ResultSet|array $data,
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

			/** @var array<Entities\CrudEntity> $entity */
			$entity = $data->toArray();

		} elseif (is_array($data)) {
			/** @var array<Entities\CrudEntity> $entity */
			$entity = $data;

		} else {
			$entity = $data;
		}

		return $this->builder->build(
			$request,
			$response,
			$entity,
			$totalCount,
			fn (string $link): bool => $this->routesValidator->validate($link),
		);
	}

}
