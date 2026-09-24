<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Zigbee2MQTT device entity hydrator
 *
 * @template  T of Entities\Devices\Device
 * @extends   DevicesHydrators\Devices\Device<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends DevicesHydrators\Devices\Device
{

	public function __construct(
		protected readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateConnectorRelationship(
		Objects\IRelationshipObject $relationship,
		Objects\IResourceObjectCollection|null $included,
		Entities\Devices\Device|null $entity,
	): Entities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$connector = $this->connectorsRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
				Entities\Connectors\Connector::class,
			);

			if ($connector !== null) {
				return $connector;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//zigbee2mqtt-connector.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//zigbee2mqtt-connector.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR . '/data/id',
			],
		);
	}

}
