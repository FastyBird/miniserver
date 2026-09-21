<?php declare(strict_types = 1);

/**
 * Viera.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\JsonApi as JsonApiHelpers;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Viera device entity hydrator
 *
 * @extends HomeKitHydrators\Devices\Device<Entities\Devices\Viera>
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Viera extends HomeKitHydrators\Devices\Device
{

	/** @var array<int|string, string> */
	protected array $attributes
		= [
			'category',
			'identifier',
			'name',
			'comment',
			'params',
		];

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		JsonApiHelpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($connectorsRepository, $managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Devices\Viera::class;
	}

	/**
	 * @param Entities\Devices\Viera|null $entity
	 *
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateConnectorRelationship(
		JsonApi\Objects\IRelationshipObject $relationship,
		JsonApi\Objects\IResourceObjectCollection|null $included,
		HomeKitEntities\Devices\Device|null $entity,
	): HomeKitEntities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof JsonApi\Objects\IResourceIdentifierObject
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
				'//viera-connector-homekit-connector-bridge.base.messages.invalidRelation.heading',
			)),
			strval($this->translator->translate(
				'//viera-connector-homekit-connector-bridge.base.messages.invalidRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Viera::RELATIONSHIPS_CONNECTOR . '/data/id',
			],
		);
	}

	/**
	 * @return array<DevicesEntities\Devices\Device>
	 *
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateParentsRelationship(
		JsonApi\Objects\IRelationshipObject $relationships,
		JsonApi\Objects\IResourceObjectCollection|null $included,
		Entities\Devices\Viera|null $entity,
	): array
	{
		if ($relationships->getData() instanceof JsonApi\Objects\ResourceIdentifierCollection) {
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

					if ($parent instanceof VieraEntities\Devices\Device) {
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
				'//viera-connector-homekit-connector-bridge.base.messages.missingRelation.heading',
			)),
			strval($this->translator->translate(
				'//viera-connector-homekit-connector-bridge.base.messages.missingRelation.message',
			)),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Viera::RELATIONSHIPS_PARENTS . '/data/id',
			],
		);
	}

}
