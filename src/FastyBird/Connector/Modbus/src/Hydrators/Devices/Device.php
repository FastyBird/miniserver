<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           30.01.22
 */

namespace FastyBird\Connector\Modbus\Hydrators\Devices;

use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Schemas;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Helpers\JsonApi as JsonApiHelpers;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Modbus device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\Devices\Device>
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends DevicesHydrators\Devices\Device
{

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		JsonApiHelpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateConnectorRelationship(
		JsonApi\Objects\IRelationshipObject $relationship,
		JsonApi\Objects\IResourceObjectCollection|null $included,
		Entities\Devices\Device|null $entity,
	): Entities\Connectors\Connector
	{
		if (
			$relationship->getData() instanceof JsonApi\Objects\IResourceIdentifierObject
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

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//modbus-connector.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//modbus-connector.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR . '/data/id',
			],
		);
	}

}
