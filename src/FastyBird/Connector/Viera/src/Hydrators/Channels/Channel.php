<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Viera\Hydrators\Channels;

use Doctrine\Persistence;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Schemas;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Helpers\JsonApi as JsonApiHelpers;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Viera channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\Channels\Channel>
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel extends DevicesHydrators\Channels\Channel
{

	public function __construct(
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		JsonApiHelpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Channels\Channel::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateDeviceRelationship(
		JsonApi\Objects\IRelationshipObject $relationship,
		JsonApi\Objects\IResourceObjectCollection|null $included,
		Entities\Channels\Channel|null $entity,
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
			strval($this->translator->translate('//viera-connector.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//viera-connector.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Channels\Channel::RELATIONSHIPS_DEVICE . '/data/id',
			],
		);
	}

}
