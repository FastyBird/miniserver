<?php declare(strict_types = 1);

/**
 * TabsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui\Controllers;
use FastyBird\Module\Ui\Exceptions;
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
 * Dashboard tabs API controller
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class TabsV1 extends BaseV1
{

	use Controllers\Finders\TDashboard;
	use Controllers\Finders\TTab;

	public function __construct(
		protected readonly Models\Entities\Dashboards\Repository $dashboardsRepository,
		protected readonly Models\Entities\Dashboards\Tabs\Repository $tabsRepository,
		protected readonly Models\Entities\Dashboards\Tabs\Manager $tabsManager,
		protected readonly Models\Entities\Widgets\Repository $widgetsRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		if ($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID) !== null) {
			// At first, try to load dashboard
			$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));

			$findQuery = new Queries\Entities\FindDashboardTabs();
			$findQuery->forDashboard($dashboard);

			$tabs = $this->tabsRepository->getResultSet($findQuery);
		} else {
			$findQuery = new Queries\Entities\FindDashboardTabs();

			$tabs = $this->tabsRepository->getResultSet($findQuery);
		}

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $tabs);
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
		// At first, try to load dashboard
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));
		// & tab
		$tab = $this->findTab(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $dashboard);

		return $this->buildResponse($request, $response, $tab);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		if ($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID) !== null) {
			// At first, try to load dashboard
			$this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));
		}

		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$tab = $this->tabsManager->create($hydrator->hydrate($document));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (DoctrineCrudExceptions\MissingRequiredField $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (DoctrineCrudExceptions\EntityCreation $ex) {
				throw new JsonApiExceptions\JsonApiError(
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
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.heading')),
					strval($this->translator->translate('//ui-module.base.messages.uniqueIdentifier.message')),
					[
						'pointer' => '/data/id',
					],
				);
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new JsonApiExceptions\JsonApiError(
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

					if (str_starts_with($columnKey, 'tab_')) {
						throw new JsonApiExceptions\JsonApiError(
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

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.heading')),
					strval($this->translator->translate('//ui-module.base.messages.uniqueAttribute.message')),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::UI->value,
						'type' => 'tabs-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
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

			$response = $this->buildResponse($request, $response, $tab);

			return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
		}

		throw new JsonApiExceptions\JsonApiError(
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
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load dashboard
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));
		// & tab
		$tab = $this->findTab(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $dashboard);

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$tab = $this->tabsManager->update($tab, $hydrator->hydrate($document, $tab));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::UI->value,
						'type' => 'tabs-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
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

			return $this->buildResponse($request, $response, $tab);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ui-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//ui-module.base.messages.invalidType.message')),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws ApplicationExceptions\InvalidState
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load dashboard
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));
		// & tab
		$tab = $this->findTab(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $dashboard);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Remove tab
			$this->tabsManager->delete($tab);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::UI->value,
					'type' => 'tabs-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
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
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load dashboard
		$dashboard = $this->findDashboard(strval($request->getAttribute(Router\ApiRoutes::URL_DASHBOARD_ID)));
		// & tab
		$tab = $this->findTab(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $dashboard);

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Dashboards\Tabs\Tab::RELATIONSHIPS_WIDGETS) {
			$findWidgetsQuery = new Queries\Entities\FindWidgets();
			$findWidgetsQuery->inTab($tab);

			return $this->buildResponse(
				$request,
				$response,
				$this->widgetsRepository->findAllBy($findWidgetsQuery),
			);
		}

		return parent::readRelationship($request, $response);
	}

}
