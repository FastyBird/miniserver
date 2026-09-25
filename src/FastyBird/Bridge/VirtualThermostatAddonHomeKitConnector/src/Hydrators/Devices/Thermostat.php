<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Thermostat device entity hydrator
 *
 * @extends HomeKitHydrators\Devices\Device<Entities\Devices\Thermostat>
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat extends HomeKitHydrators\Devices\Device
{

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($connectorsRepository, $managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Devices\Thermostat::class;
	}

	/**
	 * @param Entities\Devices\Thermostat|null $entity
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
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidRelation.heading',
			)),
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Thermostat::RELATIONSHIPS_CONNECTOR . '/data/id',
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
		Entities\Devices\Thermostat|null $entity,
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

					if ($parent instanceof VirtualThermostatEntities\Devices\Device) {
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
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.heading',
			)),
			strval($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Thermostat::RELATIONSHIPS_PARENTS . '/data/id',
			],
		);
	}

}
