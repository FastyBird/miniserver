<?php declare(strict_types = 1);

/**
 * DataSourcesV1Controller.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           27.05.20
 */

namespace FastyBird\Module\Ui\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Controllers;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Utilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strval;

/**
 * API widgets display controller
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class DataSourcesV1 extends BaseV1
{

	use Controllers\Finders\TWidget;

	public function __construct(
		private readonly Models\Entities\Widgets\DataSources\Repository $dataSourcesRepository,
		private readonly Models\Entities\Widgets\DataSources\Manager $dataSourcesManager,
		private readonly Models\Entities\Widgets\Repository $widgetsRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws ApiExceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$widget = $this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$findQuery = new Queries\Entities\FindWidgetDataSources();
		$findQuery->forWidget($widget);

		$dataSources = $this->dataSourcesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $dataSources);
	}

	/**
	 * @throws Exception
	 * @throws ApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$widget = $this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$dataSource = $this->findDataSource(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $widget);

		return $this->buildResponse($request, $response, $dataSource);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws ApiExceptions\JsonApi
	 * @throws ApiExceptions\JsonApiError
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$dataSource = $this->dataSourcesManager->create($hydrator->hydrate($document));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (ApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (PersistenceExceptions\MissingRequiredField $ex) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (PersistenceExceptions\EntityCreation $ex) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (Doctrine\ORM\Exception\EntityIdentityCollisionException) {
				// ORM 3 detects a client-supplied duplicate id while adding to the identity
				// map, which happens before the INSERT that used to surface this as a DBAL
				// unique constraint violation on PRIMARY. Same condition, reported earlier.
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.heading')),
					strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.message')),
					[
						'pointer' => '/data/id',
					],
				);
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new ApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.heading')),
						strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.message')),
						[
							'pointer' => '/data/id',
						],
					);
				} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'dataSource_')) {
						throw new ApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.heading')),
							strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.message')),
							[
								'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
									Utils\Strings::substring($columnKey, 7),
								),
							],
						);
					}
				}

				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.heading')),
					strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.message')),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'dataSources-controller',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.notCreated.heading')),
					strval($this->translator->translate('//ui-module.base.messages.notCreated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			$response = $this->buildResponse($request, $response, $dataSource);

			return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ui-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//ui-module.base.messages.invalidType.message')),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws ApiExceptions\JsonApi
	 * @throws ApiExceptions\JsonApiError
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$widget = $this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$dataSource = $this->findDataSource(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $widget);

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$dataSource = $this->dataSourcesManager->update(
					$dataSource,
					$hydrator->hydrate($document, $dataSource),
				);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (ApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'dataSources-controller',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.notUpdated.heading')),
					strval($this->translator->translate('//ui-module.base.messages.notUpdated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $dataSource);
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ui-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//ui-module.base.messages.invalidType.message')),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws Doctrine\DBAL\Exception
	 * @throws PersistenceExceptions\Query
	 * @throws UiExceptions\InvalidState
	 * @throws UiExceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws ApiExceptions\JsonApi
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$widget = $this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$dataSource = $this->findDataSource(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $widget);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Remove dataSource
			$this->dataSourcesManager->delete($dataSource);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'dataSources-controller',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.notDeleted.heading')),
				strval($this->translator->translate('//ui-module.base.messages.notDeleted.message')),
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
	 * @throws ApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load widget
		$widget = $this->findWidget(strval($request->getAttribute(Router\ApiRoutes::URL_WIDGET_ID)));

		$dataSource = $this->findDataSource(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $widget);

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($dataSource->hasRelation($relationEntity)) {
			$entity = $dataSource->getRelation($relationEntity);
			assert($entity !== null);

			return $this->buildResponse($request, $response, $entity);
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws ApiExceptions\JsonApi
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findDataSource(
		string $id,
		Entities\Widgets\Widget $widget,
	): Entities\Widgets\DataSources\DataSource
	{
		try {
			$findQuery = new Queries\Entities\FindWidgetDataSources();
			$findQuery->forWidget($widget);
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$dataSource = $this->dataSourcesRepository->findOneBy($findQuery);

			if ($dataSource === null) {
				throw new ApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//ui-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//ui-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//ui-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//ui-module.base.messages.notFound.message')),
			);
		}

		return $dataSource;
	}

}
