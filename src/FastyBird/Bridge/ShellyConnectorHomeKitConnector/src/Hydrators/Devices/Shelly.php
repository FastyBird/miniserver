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
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\JsonApi\Helpers;
use FastyBird\JsonApi\JsonApi as JsonApiJsonApi;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineCrud\Entities as DoctrineCrudEntities;
use IPub\JsonAPIDocument;
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
 * @extends HomeKitHydrators\Devices\Device<Entities\Devices\Shelly>
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

	/** @var JsonApiJsonApi\SchemaContainer<DoctrineCrudEntities\IEntity>|null */
	private JsonApiJsonApi\SchemaContainer|null $jsonApiSchemaContainer = null;

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
		return Entities\Devices\Shelly::class;
	}

	/**
	 * @param Entities\Devices\Shelly|null $entity
	 *
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateConnectorRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
		HomeKitEntities\Devices\Device|null $entity,
	): HomeKitEntities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof JsonAPIDocument\Objects\IResourceIdentifierObject
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

		throw new JsonApiExceptions\JsonApiError(
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
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateParentsRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationships,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
		Entities\Devices\Shelly|null $entity,
	): array
	{
		if ($relationships->getData() instanceof JsonAPIDocument\Objects\ResourceIdentifierCollection) {
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

		throw new JsonApiExceptions\JsonApiError(
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
	 * @throws JsonApiExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Throwable
	 */
	protected function hydratePropertiesRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
	): array
	{
		if ($included === null) {
			throw new JsonApiExceptions\JsonApiError(
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
								$document = JsonAPIDocument\Document::create(Utils\Json::encode([
									'data' => [
										'id' => $item->getId(),
										'type' => $item->getType(),
										'attributes' => $item->getAttributes()->toArray(),
									],
								]));

								$properties[] = $propertyHydrator->hydrate($document, null, false);

							} catch (JsonApiExceptions\JsonApi | JsonException) {
								throw new JsonApiExceptions\JsonApiError(
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
	 * @return JsonApiJsonApi\SchemaContainer<DoctrineCrudEntities\IEntity>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getSchemaContainer(): JsonApiJsonApi\SchemaContainer
	{
		if ($this->jsonApiSchemaContainer !== null) {
			return $this->jsonApiSchemaContainer;
		}

		$this->jsonApiSchemaContainer = $this->container->getByType(JsonApiJsonApi\SchemaContainer::class);

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
