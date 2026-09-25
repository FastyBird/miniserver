<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           08.04.24
 */

namespace FastyBird\Module\Devices\Hydrators\Channels;

use Doctrine\Persistence;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Generic channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Generic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends Channel
{

	public function __construct(
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Channels\Generic::class;
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateDeviceRelationship(
		Objects\IRelationshipObject $relationship,
		Objects\IResourceObjectCollection|null $included,
		Entities\Channels\Channel|null $entity,
	): Entities\Devices\Device
	{
		if (
			$relationship->getData() instanceof Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$device = $this->devicesRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
			);

			if ($device !== null) {
				return $device;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//devices-module.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//devices-module.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Channels\Channel::RELATIONSHIPS_DEVICE . '/data/id',
			],
		);
	}

}
