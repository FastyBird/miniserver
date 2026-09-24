<?php declare(strict_types = 1);

/**
 * BaseV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Controllers;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence;
use Exception;
use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Router;
use FastyBird\Core\Documents;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi as JsonApiBuilder;
use FastyBird\Core\Entities\DoctrineCrud;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Persistence\DoctrineOrmQuery\ResultSet;
use FastyBird\Core\Persistence\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Devices\Router as DevicesRouter;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Http\Message\ResponseInterface;
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
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BaseV1
{

	use Nette\SmartObject;

	protected Localization\Translator $translator;

	protected Persistence\ManagerRegistry $managerRegistry;

	protected JsonApiBuilder\Builder $builder;

	protected DevicesRouter\Validator $routesValidator;

	/** @var JsonApiHydrators\Container<DoctrineCrud\IEntity> */
	protected JsonApiHydrators\Container $hydratorsContainer;

	protected VieraConnectorHomeKitConnector\Logger $logger;

	public function setLogger(VieraConnectorHomeKitConnector\Logger $logger): void
	{
		$this->logger = $logger;
	}

	public function injectTranslator(Localization\Translator $translator): void
	{
		$this->translator = $translator;
	}

	public function injectManagerRegistry(Persistence\ManagerRegistry $managerRegistry): void
	{
		$this->managerRegistry = $managerRegistry;
	}

	public function injectJsonApiBuilder(JsonApiBuilder\Builder $builder): void
	{
		$this->builder = $builder;
	}

	public function injectRoutesValidator(DevicesRouter\Validator $validator): void
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
		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity !== '') {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.relationNotFound.heading',
				)),
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.relationNotFound.message',
					['relation' => $relationEntity],
				)),
			);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate(
				'//viera-connector-homekit-connector-bridge.base.messages.unknownRelation.heading',
			)),
			strval($this->translator->translate(
				'//viera-connector-homekit-connector-bridge.base.messages.unknownRelation.message',
			)),
		);
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 */
	protected function createDocument(Message\ServerRequestInterface $request): JsonApi\IDocument
	{
		try {
			$content = Utils\Json::decode($request->getBody()->getContents());

			if (!$content instanceof stdClass) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_BAD_REQUEST,
					strval($this->translator->translate(
						'//viera-connector-homekit-connector-bridge.base.messages.notValidJsonApi.heading',
					)),
					strval($this->translator->translate(
						'//viera-connector-homekit-connector-bridge.base.messages.notValidJsonApi.message',
					)),
				);
			}

			$document = new JsonApi\Document($content);

		} catch (Utils\JsonException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.notValidJson.heading',
				)),
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.notValidJson.message',
				)),
			);
		} catch (ApplicationExceptions\Runtime) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.notValidJsonApi.heading',
				)),
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.notValidJsonApi.message',
				)),
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
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.invalidIdentifier.heading',
				)),
				strval($this->translator->translate(
					'//viera-connector-homekit-connector-bridge.base.messages.invalidIdentifier.message',
				)),
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

		throw new Exceptions\Runtime('Entity manager could not be loaded');
	}

	/**
	 * @param DoctrineCrud\IEntity|Documents\Document|ResultSet<DoctrineCrud\IEntity>|array<DoctrineCrud\IEntity> $data
	 *
	 * @throws Exception
	 */
	protected function buildResponse(
		Message\ServerRequestInterface $request,
		ResponseInterface $response,
		ResultSet|DoctrineCrud\IEntity|Documents\Document|array $data,
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

			/** @var array<DoctrineCrud\IEntity> $entity */
			$entity = $data->toArray();

		} elseif (is_array($data)) {
			/** @var array<DoctrineCrud\IEntity> $entity */
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
