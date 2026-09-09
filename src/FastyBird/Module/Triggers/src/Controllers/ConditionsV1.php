<?php declare(strict_types = 1);

/**
 * ConditionsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Utilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strtolower;
use function strval;

/**
 * Triggers conditions controller
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ConditionsV1 extends BaseV1
{

	use Controllers\Finders\TTrigger;

	public function __construct(
		protected readonly Models\Entities\Triggers\TriggersRepository $triggersRepository,
		private readonly Models\Entities\Conditions\ConditionsRepository $conditionsRepository,
		private readonly Models\Entities\Conditions\ConditionsManager $conditionsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Automatic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		$findQuery = new Queries\Entities\FindConditions();
		$findQuery->forTrigger($trigger);

		$rows = $this->conditionsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $rows);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Automatic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		// & condition
		$condition = $this->findCondition(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $trigger);

		return $this->buildResponse($request, $response, $condition);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if ($trigger instanceof Entities\Triggers\Automatic) {
			$document = $this->createDocument($request);

			$hydrator = $this->hydratorsContainer->findHydrator($document);

			if ($hydrator !== null) {
				try {
					// Start transaction connection to the database
					$this->getOrmConnection()->beginTransaction();

					$condition = $this->conditionsManager->create($hydrator->hydrate($document));

					// Commit all changes into database
					$this->getOrmConnection()->commit();

				} catch (DoctrineCrudExceptions\MissingRequiredField $ex) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval(
							$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
						),
						strval(
							$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
						),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
						],
					);
				} catch (DoctrineCrudExceptions\EntityCreation $ex) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval(
							$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
						),
						strval(
							$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
						),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
						],
					);
				} catch (JsonApiExceptions\JsonApi $ex) {
					throw $ex;
				} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
					if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
						throw new JsonApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							strval(
								$this->translator->translate(
									'//triggers-module.base.messages.uniqueIdentifier.heading',
								),
							),
							strval(
								$this->translator->translate(
									'//triggers-module.base.messages.uniqueIdentifier.message',
								),
							),
							[
								'pointer' => '/data/id',
							],
						);
					} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
						$columnParts = explode('.', $match['key']);
						$columnKey = end($columnParts);

						if (str_starts_with($columnKey, 'condition_')) {
							throw new JsonApiExceptions\JsonApiError(
								StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
								strval(
									$this->translator->translate(
										'//triggers-module.base.messages.uniqueAttribute.heading',
									),
								),
								strval(
									$this->translator->translate(
										'//triggers-module.base.messages.uniqueAttribute.message',
									),
								),
								[
									'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
										Utils\Strings::substring($columnKey, 10),
									),
								],
							);
						}
					}

					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//triggers-module.base.messages.uniqueAttribute.heading')),
						strval($this->translator->translate('//triggers-module.base.messages.uniqueAttribute.message')),
					);
				} catch (Throwable $ex) {
					// Log caught exception
					$this->logger->error(
						'An unhandled error occurred',
						[
							'source' => MetadataTypes\Sources\Module::TRIGGERS->value,
							'type' => 'conditions-controller',
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);

					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//triggers-module.base.messages.notCreated.heading')),
						strval($this->translator->translate('//triggers-module.base.messages.notCreated.message')),
					);
				} finally {
					// Revert all changes when error occur
					if ($this->getOrmConnection()->isTransactionActive()) {
						$this->getOrmConnection()->rollBack();
					}
				}

				$response = $this->buildResponse($request, $response, $condition);

				return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
			}

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.invalidType.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.invalidType.message')),
				[
					'pointer' => '/data/type',
				],
			);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
			strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
		);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Automatic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		// & condition
		$condition = $this->findCondition(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $trigger);

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$condition = $this->conditionsManager->update($condition, $hydrator->hydrate($document, $condition));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::TRIGGERS->value,
						'type' => 'conditions-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//triggers-module.base.messages.notUpdated.heading')),
					strval($this->translator->translate('//triggers-module.base.messages.notUpdated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $condition);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//triggers-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//triggers-module.base.messages.invalidType.message')),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Automatic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		// & condition
		$condition = $this->findCondition(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $trigger);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->conditionsManager->delete($condition);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::TRIGGERS->value,
					'type' => 'conditions-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.notUpdated.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notDeleted.message')),
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
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load trigger
		$trigger = $this->findTrigger(strval($request->getAttribute(Router\ApiRoutes::URL_TRIGGER_ID)));

		if (!$trigger instanceof Entities\Triggers\Automatic) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		// & condition
		$condition = $this->findCondition(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $trigger);

		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Conditions\Condition::RELATIONSHIPS_TRIGGER) {
			return $this->buildResponse($request, $response, $condition->getTrigger());
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findCondition(
		string $id,
		Entities\Triggers\Trigger $trigger,
	): Entities\Conditions\Condition
	{
		try {
			$findQuery = new Queries\Entities\FindConditions();
			$findQuery->byId(Uuid\Uuid::fromString($id));
			$findQuery->forTrigger($trigger);

			$condition = $this->conditionsRepository->findOneBy($findQuery);

			if ($condition === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//triggers-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.notFound.message')),
			);
		}

		return $condition;
	}

}
