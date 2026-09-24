<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
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
 * Zigbee2MQTT sub-device device entity hydrator
 *
 * @extends Device<Entities\Devices\SubDevice>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SubDevice extends Device
{

	public function __construct(
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($connectorsRepository, $managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Devices\SubDevice::class;
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
		Entities\Devices\SubDevice|null $entity,
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

					if ($parent instanceof Entities\Devices\Bridge) {
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
			strval($this->translator->translate('//zigbee2mqtt-connector.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//zigbee2mqtt-connector.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\SubDevice::RELATIONSHIPS_PARENTS . '/data/id',
			],
		);
	}

}
