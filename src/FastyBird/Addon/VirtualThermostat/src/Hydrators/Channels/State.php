<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           03.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Hydrators\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * State channel entity hydrator
 *
 * @extends VirtualHydrators\Channels\Channel<Entities\Channels\State>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State extends VirtualHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\State::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateDeviceRelationship(
		JsonApi\Objects\IRelationshipObject $relationship,
		JsonApi\Objects\IResourceObjectCollection|null $included,
		VirtualEntities\Channels\Channel|null $entity,
	): Entities\Devices\Device
	{
		if (
			$relationship->getData() instanceof JsonApi\Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$device = $this->devicesRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
				Entities\Devices\Device::class,
			);

			if ($device !== null) {
				return $device;
			}
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//virtual-thermostat-addon.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//virtual-thermostat-addon.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Channels\State::RELATIONSHIPS_DEVICE . '/data/id',
			],
		);
	}

}
