<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           21.07.23
 */

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Schemas;
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
 * NS Panel channel entity hydrator
 *
 * @template  T of Entities\Channels\Channel
 * @extends   DevicesHydrators\Channels\Channel<T>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Channel extends DevicesHydrators\Channels\Channel
{

	public function __construct(
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
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
				Entities\Devices\Device::class,
			);

			if ($device !== null) {
				return $device;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ns-panel-connector.base.messages.invalidRelation.heading')),
			strval($this->translator->translate('//ns-panel-connector.base.messages.invalidRelation.message')),
			[
				'pointer' => '/data/relationships/' . Schemas\Channels\Channel::RELATIONSHIPS_DEVICE . '/data/id',
			],
		);
	}

}
