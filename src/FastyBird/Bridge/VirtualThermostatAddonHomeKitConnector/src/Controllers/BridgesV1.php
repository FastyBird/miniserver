<?php declare(strict_types = 1);

/**
 * BridgesV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;

use Doctrine;
use Exception;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions as VirtualThermostatAddonHomeKitConnectorExceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
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
 * API bridges controller
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
class BridgesV1 extends BaseV1
{

	public function __construct(
		private readonly Builders\Builder $bridgeBuilder,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
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
		$findQuery = new Queries\Entities\FindThermostatDevices();

		$devices = $this->devicesRepository->getResultSet(
			$findQuery,
			Entities\Devices\Thermostat::class,
		);

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
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		return $this->buildResponse($request, $response, $device);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator instanceof Hydrators\Devices\Thermostat) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->create($hydrator->hydrate($document));
				assert($device instanceof Entities\Devices\Thermostat);

				$device = $this->bridgeBuilder->build(
					$device->getParent(),
					$device->getConnector(),
				);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (PersistenceExceptions\MissingRequiredField $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.message',
					)),
					[
						'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (PersistenceExceptions\EntityCreation $ex) {
				if ($ex->getField() === Schemas\Devices\Thermostat::RELATIONSHIPS_PARENTS) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.heading',
						)),
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.message',
						)),
						[
							'pointer' => '/data/relationships/' . Schemas\Devices\Thermostat::RELATIONSHIPS_PARENTS . '/data/id',
						],
					);
				} else {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.heading',
						)),
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.message',
						)),
						[
							'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi($ex->getField()),
						],
					);
				}
			} catch (Doctrine\ORM\Exception\EntityIdentityCollisionException) {
				// ORM 3 detects a client-supplied duplicate id while adding to the identity
				// map, which happens before the INSERT that used to surface this as a DBAL
				// unique constraint violation on PRIMARY. Same condition, reported earlier.
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.message',
					)),
					[
						'pointer' => '/data/id',
					],
				);
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.heading',
						)),
						strval($this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.message',
						)),
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
							strval($this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
							)),
							strval($this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
							)),
							[
								'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi(
									Utils\Strings::substring($columnKey, 7),
								),
							],
						);
					}
				}

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
					)),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'bridges-controller',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notCreated.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notCreated.message',
					)),
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
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.heading',
			)),
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.message',
			)),
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
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator instanceof Hydrators\Devices\Thermostat) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->update($device, $hydrator->hydrate($document, $device));
				assert($device instanceof Entities\Devices\Thermostat);

				$device = $this->bridgeBuilder->build(
					$device->getParent(),
					$device->getConnector(),
				);

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
							strval($this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
							)),
							strval($this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
							)),
							[
								'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi(
									Utils\Strings::substring($columnKey, 7),
								),
							],
						);
					}
				}

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
					)),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'bridges-controller',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notUpdated.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notUpdated.message',
					)),
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
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.heading',
			)),
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.message',
			)),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Doctrine\DBAL\Exception
	 * @throws PersistenceExceptions\Query
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidState
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws ApplicationExceptions\InvalidState
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
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
					'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'bridges-controller',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notDeleted.heading',
				)),
				strval($this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notDeleted.message',
				)),
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
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function findDevice(string $id): Entities\Devices\Thermostat
	{
		try {
			$device = $this->devicesRepository->find(
				Uuid\Uuid::fromString($id),
				Entities\Devices\Thermostat::class,
			);

			if ($device === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.heading',
					)),
					strval($this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.message',
					)),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.heading',
				)),
				strval($this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.message',
				)),
			);
		}

		return $device;
	}

}
