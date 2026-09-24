<?php declare(strict_types = 1);

/**
 * ChannelsV1.php
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
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
 * Device channels API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @Secured\User(loggedIn)
 */
final class ChannelsV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TChannel;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly Models\Entities\Channels\ChannelsManager $channelsManager,
		protected readonly Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		protected readonly Models\Entities\Channels\Controls\ControlsRepository $channelControlsRepository,
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
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));

			$findQuery = new Queries\Entities\FindChannels();
			$findQuery->forDevice($device);

			$channels = $this->channelsRepository->getResultSet($findQuery);
		} else {
			$findQuery = new Queries\Entities\FindChannels();

			$channels = $this->channelsRepository->getResultSet($findQuery);
		}

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $channels);
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
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
			// & channel
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $device);
		} else {
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		return $this->buildResponse($request, $response, $channel);
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
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
		}

		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$channel = $this->channelsManager->create($hydrator->hydrate($document));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (PersistenceExceptions\MissingRequiredField $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (PersistenceExceptions\EntityCreation $ex) {
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

					if (str_starts_with($columnKey, 'channel_')) {
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
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channels-controller',
						'exception' => Logging\Logger::buildException($ex),
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

			$response = $this->buildResponse($request, $response, $channel);

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
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
			// & channel
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $device);
		} else {
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$channel = $this->channelsManager->update($channel, $hydrator->hydrate($document, $channel));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channels-controller',
						'exception' => Logging\Logger::buildException($ex),
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

			return $this->buildResponse($request, $response, $channel);
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
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
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
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
			// & channel
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $device);
		} else {
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Remove channel
			$this->channelsManager->delete($channel);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channels-controller',
					'exception' => Logging\Logger::buildException($ex),
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
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		if ($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null) {
			// At first, try to load device
			$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)));
			// & channel
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $device);
		} else {
			$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));
		}

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Channels\Channel::RELATIONSHIPS_PROPERTIES) {
			$findChannelPropertiesQuery = new Queries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			return $this->buildResponse(
				$request,
				$response,
				$this->channelPropertiesRepository->findAllBy($findChannelPropertiesQuery),
			);
		} elseif ($relationEntity === Schemas\Channels\Channel::RELATIONSHIPS_CONTROLS) {
			$findChannelControlsQuery = new Queries\Entities\FindChannelControls();
			$findChannelControlsQuery->forChannel($channel);

			return $this->buildResponse(
				$request,
				$response,
				$this->channelControlsRepository->findAllBy($findChannelControlsQuery),
			);
		}

		return parent::readRelationship($request, $response);
	}

}
