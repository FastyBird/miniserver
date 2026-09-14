<?php declare(strict_types = 1);

/**
 * ChannelPropertiesV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.06.19
 */

namespace FastyBird\Module\Devices\Controllers;

use Doctrine;
use Exception;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Library\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
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
use Ramsey\Uuid;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strval;

/**
 * Device channel properties API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
final class ChannelPropertiesV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TChannel;
	use Controllers\Finders\TChannelProperty;

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		protected readonly Models\Entities\Channels\Properties\PropertiesManager $channelPropertiesManager,
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
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		$findQuery = new Queries\Entities\FindChannelProperties();
		$findQuery->forChannel($channel);

		$properties = $this->channelPropertiesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $properties);
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
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);
		// & property
		$property = $this->findProperty(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $channel);

		return $this->buildResponse($request, $response, $property);
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
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$property = $this->channelPropertiesManager->create($hydrator->hydrate($document));

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
						'/pointer' => 'data/attributes/' . $ex->getField(),
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

					if (str_starts_with($columnKey, 'property_')) {
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
						'type' => 'channel-properties-controller',
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

			$response = $this->buildResponse($request, $response, $property);

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
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);
		// & property
		$property = $this->findProperty(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $channel);

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$property = $this->channelPropertiesManager->update(
					$property,
					$hydrator->hydrate($document, $property),
				);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'channel-properties-controller',
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

			return $this->buildResponse($request, $response, $property);
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
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);
		// & property
		$property = $this->findProperty(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $channel);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Remove channel
			$this->channelPropertiesManager->delete($property);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'channel-properties-controller',
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
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID) !== null
			? $this->findDevice(
				strval($request->getAttribute(Router\ApiRoutes::URL_DEVICE_ID)),
			)
			: null;

		// & channel
		$channel = $this->findChannel(strval($request->getAttribute(Router\ApiRoutes::URL_CHANNEL_ID)), $device);

		// & relation entity name
		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if (Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			// & property
			$property = $this->findProperty(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)), $channel);

			if ($relationEntity === Schemas\Channels\Properties\Property::RELATIONSHIPS_CHANNEL) {
				return $this->buildResponse($request, $response, $property->getChannel());
			} elseif (
				$relationEntity === Schemas\Channels\Properties\Property::RELATIONSHIPS_PARENT
				&& $property->getParent() !== null
			) {
				return $this->buildResponse($request, $response, $property->getParent());
			} elseif ($relationEntity === Schemas\Channels\Properties\Property::RELATIONSHIPS_CHILDREN) {
				return $this->buildResponse($request, $response, $property->getChildren());
			}
		}

		return parent::readRelationship($request, $response);
	}

}
