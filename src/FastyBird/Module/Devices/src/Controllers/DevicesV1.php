<?php declare(strict_types = 1);

/**
 * DevicesController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Utilities;
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
 * API devices controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
class DevicesV1 extends BaseV1
{

	use Controllers\Finders\TConnector;
	use Controllers\Finders\TDevice;

	public function __construct(
		protected readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Devices\DevicesManager $devicesManager,
		protected readonly Models\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		protected readonly Models\Entities\Devices\Controls\ControlsRepository $deviceControlsRepository,
		protected readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly Models\Entities\Channels\ChannelsManager $channelsManager,
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
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

			$findQuery = new Queries\Entities\FindDevices();
			$findQuery->forConnector($connector);

			$devices = $this->devicesRepository->getResultSet($findQuery);
		} else {
			$findQuery = new Queries\Entities\FindDevices();

			$devices = $this->devicesRepository->getResultSet($findQuery);
		}

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $devices);
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
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $connector);
		} else {
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		return $this->buildResponse($request, $response, $device);
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
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));
		}

		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->create($hydrator->hydrate($document));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (DoctrineCrudExceptions\MissingRequiredField $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (DoctrineCrudExceptions\EntityCreation $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.message')),
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
					strval($this->translator->translate('//devices-module.base.messages.uniqueIdentifier.heading')),
					strval($this->translator->translate('//devices-module.base.messages.uniqueIdentifier.message')),
					[
						'pointer' => '/data/id',
					],
				);
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate('//devices-module.base.messages.uniqueIdentifier.heading')),
						strval($this->translator->translate('//devices-module.base.messages.uniqueIdentifier.message')),
						[
							'pointer' => '/data/id',
						],
					);
				} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'device_')) {
						throw new JsonApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							strval(
								$this->translator->translate('//devices-module.base.messages.uniqueAttribute.heading'),
							),
							strval(
								$this->translator->translate('//devices-module.base.messages.uniqueAttribute.message'),
							),
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
					strval($this->translator->translate('//devices-module.base.messages.uniqueAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.uniqueAttribute.message')),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'devices-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.notCreated.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notCreated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			$response = $this->buildResponse($request, $response, $device);

			return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//devices-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//devices-module.base.messages.invalidType.message')),
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
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $connector);
		} else {
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->update($device, $hydrator->hydrate($document, $device));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) !== false) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'device_')) {
						throw new JsonApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							strval(
								$this->translator->translate('//devices-module.base.messages.uniqueAttribute.heading'),
							),
							strval(
								$this->translator->translate('//devices-module.base.messages.uniqueAttribute.message'),
							),
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
					strval($this->translator->translate('//devices-module.base.messages.uniqueAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.uniqueAttribute.message')),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'devices-controller',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.notUpdated.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notUpdated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $device);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//devices-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//devices-module.base.messages.invalidType.message')),
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
	 * @throws ToolsExceptions\InvalidState
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $connector);
		} else {
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$findChannelsQuery = new Queries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
				$this->channelsManager->delete($channel);
			}

			// Move device back into warehouse
			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'devices-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//devices-module.base.messages.notDeleted.heading')),
				strval($this->translator->translate('//devices-module.base.messages.notDeleted.message')),
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
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		if ($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID) !== null) {
			// At first, try to load connector
			$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_CONNECTOR_ID)));

			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $connector);
		} else {
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR) {
			return $this->buildResponse($request, $response, $device->getConnector());
		} elseif ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_PROPERTIES) {
			$findDevicePropertiesQuery = new Queries\Entities\FindDeviceProperties();
			$findDevicePropertiesQuery->forDevice($device);

			return $this->buildResponse(
				$request,
				$response,
				$this->devicePropertiesRepository->findAllBy($findDevicePropertiesQuery),
			);
		} elseif ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_CONTROLS) {
			$findDeviceControlsQuery = new Queries\Entities\FindDeviceControls();
			$findDeviceControlsQuery->forDevice($device);

			return $this->buildResponse(
				$request,
				$response,
				$this->deviceControlsRepository->findAllBy($findDeviceControlsQuery),
			);
		} elseif ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_PARENTS) {
			$findParentsDevicesQuery = new Queries\Entities\FindDevices();
			$findParentsDevicesQuery->forChild($device);

			return $this->buildResponse(
				$request,
				$response,
				$this->devicesRepository->findAllBy($findParentsDevicesQuery),
			);
		} elseif ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_CHILDREN) {
			$findChildrenDevicesQuery = new Queries\Entities\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			return $this->buildResponse(
				$request,
				$response,
				$this->devicesRepository->findAllBy($findChildrenDevicesQuery),
			);
		} elseif ($relationEntity === Schemas\Devices\Device::RELATIONSHIPS_CHANNELS) {
			$findChannelsQuery = new Queries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			return $this->buildResponse($request, $response, $this->channelsRepository->findAllBy($findChannelsQuery));
		}

		return parent::readRelationship($request, $response);
	}

}
