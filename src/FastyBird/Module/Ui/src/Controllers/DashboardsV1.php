<?php declare(strict_types = 1);

/**
 * DashboardsController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           26.05.20
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
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Schemas;
use FastyBird\Module\Ui\Utilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strval;

/**
 * API dashboards controller
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class DashboardsV1 extends BaseV1
{

	use Controllers\Finders\TDashboard;

	public function __construct(
		private readonly Models\Entities\Dashboards\Repository $dashboardsRepository,
		private readonly Models\Entities\Dashboards\Manager $dashboardsManager,
		private readonly Models\Entities\Dashboards\Tabs\Repository $tabsRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindDashboards();

		$dashboards = $this->dashboardsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $dashboards);
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
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		return $this->buildResponse($request, $response, $dashboard);
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
		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$dashboard = $this->dashboardsManager->create($hydrator->hydrate($document));

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

					if (str_starts_with($columnKey, 'dashboard_')) {
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
						'source' => Sources\Module::UI->value,
						'type' => 'dashboards-controller',
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

			$response = $this->buildResponse($request, $response, $dashboard);

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
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$dashboard = $this->dashboardsManager->update($dashboard, $hydrator->hydrate($document, $dashboard));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (ApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) !== false) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'dashboard_')) {
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
						'source' => Sources\Module::UI->value,
						'type' => 'dashboards-controller',
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

			return $this->buildResponse($request, $response, $dashboard);
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
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Move dashboard back into warehouse
			$this->dashboardsManager->delete($dashboard);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Module::UI->value,
					'type' => 'dashboards-controller',
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
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws ApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Dashboards\Dashboard::RELATIONSHIPS_TABS) {
			$findTabsQuery = new Queries\Entities\FindDashboardTabs();
			$findTabsQuery->forDashboard($dashboard);

			return $this->buildResponse($request, $response, $this->tabsRepository->findAllBy($findTabsQuery));
		}

		return parent::readRelationship($request, $response);
	}

}
