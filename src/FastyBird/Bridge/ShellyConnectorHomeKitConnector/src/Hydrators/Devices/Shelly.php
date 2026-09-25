<?php declare(strict_types = 1);

/**
 * Shelly.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities as ShellyConnectorHomeKitConnectorEntities;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use Nette\DI;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_string;
use function strval;

/**
 * Shelly device entity hydrator
 *
 * @extends HomeKitHydrators\Devices\Device<ShellyConnectorHomeKitConnectorEntities\Devices\Shelly>
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Shelly extends HomeKitHydrators\Devices\Device
{

	/** @var array<int|string, string> */
	protected array $attributes
		= [
			0 => 'category',
			1 => 'identifier',
			2 => 'name',
			3 => 'comment',
			4 => 'params',

			// TODO: Fix this - this attr are for Device/Properties/Variable
			5 => 'value',
			'data_type' => 'dataType',
		];

	/** @var array<string> */
	protected array $relationships
		= [
			DevicesSchemas\Devices\Device::RELATIONSHIPS_CONNECTOR,
			DevicesSchemas\Devices\Device::RELATIONSHIPS_PARENTS,
			DevicesSchemas\Devices\Device::RELATIONSHIPS_PROPERTIES,
		];

	/** @var Encoding\SchemaContainer<PersistenceEntities\CrudEntity>|null */
	private Encoding\SchemaContainer|null $jsonApiSchemaContainer = null;

	/** @var array<DevicesHydrators\Devices\Properties\Property<DevicesEntities\Devices\Properties\Property>>|null  */
	private array|null $propertiesHydrators = null;

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DI\Container $container,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($connectorsRepository, $managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return ShellyConnectorHomeKitConnectorEntities\Devices\Shelly::class;
	}

	/**
	 * @param ShellyConnectorHomeKitConnectorEntities\Devices\Shelly|null $entity
	 *
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateConnectorRelationship(
		Objects\IRelationshipObject $relationship,
		Objects\IResourceObjectCollection|null $included,
		HomeKitEntities\Devices\Device|null $entity,
	): HomeKitEntities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$connector = $this->connectorsRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
				HomeKitEntities\Connectors\Connector::class,
			);

			if ($connector !== null) {
				return $connector;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate(
				'//shelly-connector-homekit-connector-bridge.base.messages.invalidRelation.heading',
			)),
			strval($this->translator->translate(
				'//shelly-connector-homekit-connector-bridge.base.messages.invalidRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Shelly::RELATIONSHIPS_CONNECTOR . '/data/id',
			],
		);
	}

	/**
	 * @return array<DevicesEntities\Devices\Device>
	 *
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateParentsRelationship(
		Objects\IRelationshipObject $relationships,
		Objects\IResourceObjectCollection|null $included,
		ShellyConnectorHomeKitConnectorEntities\Devices\Shelly|null $entity,
	): array
	{
		if ($relationships->getData() instanceof Objects\ResourceIdentifierCollection) {
			$parents = [];
			$foundValidParent = false;

			foreach ($relationships->getData() as $relationship) {
				if (
					is_string($relationship->getId())
					&& Uuid\Uuid::isValid($relationship->getId())
				) {
					$parent = $this->devicesRepository->find(
						Uuid\Uuid::fromString($relationship->getId()),
					);

					if ($parent instanceof ShellyEntities\Devices\Device) {
						$foundValidParent = true;
					}

					if ($parent !== null) {
						$parents[] = $parent;
					}
				}
			}

			if ($parents !== [] && $foundValidParent) {
				return $parents;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate(
				'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.heading',
			)),
			strval($this->translator->translate(
				'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Shelly::RELATIONSHIPS_PARENTS . '/data/id',
			],
		);
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws DI\MissingServiceException
	 * @throws CoreExceptions\InvalidState
	 * @throws ApiExceptions\JsonApiError
	 * @throws Throwable
	 */
	protected function hydratePropertiesRelationship(
		Objects\IRelationshipObject $relationship,
		Objects\IResourceObjectCollection|null $included,
	): array
	{
		if ($included === null) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval(
					$this->translator->translate(
						'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.heading',
					),
				),
				strval(
					$this->translator->translate(
						'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.message',
					),
				),
				[
					'pointer' => '/data/relationships/properties/data/id',
				],
			);
		}

		$properties = [];

		$propertiesHydrators = $this->getPropertyHydrators();

		foreach ($relationship->getIdentifiers() as $propertyRelationIdentifier) {
			foreach ($included->getAll() as $item) {
				if ($item->getId() === $propertyRelationIdentifier->getId()) {
					foreach ($propertiesHydrators as $propertyHydrator) {
						$propertiesSchema = $this->getSchemaContainer()->getSchemaByClassName(
							$propertyHydrator->getEntityName(),
						);

						if ($propertiesSchema->getType() === $item->getType()) {
							try {
								$document = Encoding\Document::create(Utils\Json::encode([
									'data' => [
										'id' => $item->getId(),
										'type' => $item->getType(),
										'attributes' => $item->getAttributes()->toArray(),
									],
								]));

								$properties[] = $propertyHydrator->hydrate($document, null, false);

							} catch (ApiExceptions\JsonApi | JsonException) {
								throw new ApiExceptions\JsonApiError(
									StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
									strval(
										$this->translator->translate(
											'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.heading',
										),
									),
									strval(
										$this->translator->translate(
											'//shelly-connector-homekit-connector-bridge.base.messages.missingRelation.message',
										),
									),
									[
										'pointer' => '/data/relationships/properties/data/id',
									],
								);
							}
						}
					}
				}
			}
		}

		return $properties;
	}

	/**
	 * @return Encoding\SchemaContainer<PersistenceEntities\CrudEntity>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getSchemaContainer(): Encoding\SchemaContainer
	{
		if ($this->jsonApiSchemaContainer !== null) {
			return $this->jsonApiSchemaContainer;
		}

		$this->jsonApiSchemaContainer = $this->container->getByType(Encoding\SchemaContainer::class);

		return $this->jsonApiSchemaContainer;
	}

	/**
	 * @return array<DevicesHydrators\Devices\Properties\Property<DevicesEntities\Devices\Properties\Property>>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getPropertyHydrators(): array
	{
		if ($this->propertiesHydrators !== null) {
			return $this->propertiesHydrators;
		}

		$this->propertiesHydrators = [];

		$serviceNames = $this->container->findByType(DevicesHydrators\Devices\Properties\Property::class);

		foreach ($serviceNames as $serviceName) {
			$service = $this->container->getByName($serviceName);
			assert($service instanceof DevicesHydrators\Devices\Properties\Property);

			$this->propertiesHydrators[] = $service;
		}

		return $this->propertiesHydrators;
	}

}
