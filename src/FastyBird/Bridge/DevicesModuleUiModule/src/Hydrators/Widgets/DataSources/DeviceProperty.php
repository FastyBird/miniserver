<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Hydrators\Widgets\DataSources;

use Doctrine\Persistence;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Ui\Hydrators as UiHydrators;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Device property data source entity hydrator
 *
 * @extends UiHydrators\Widgets\DataSources\DataSource<Entities\Widgets\DataSources\DeviceProperty>
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceProperty extends UiHydrators\Widgets\DataSources\DataSource
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\DataSources\DeviceProperty::RELATIONSHIPS_PROPERTY,
		Schemas\Widgets\DataSources\DeviceProperty::RELATIONSHIPS_WIDGET,
	];

	public function __construct(
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\DeviceProperty::class;
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 * @throws CoreExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydratePropertyRelationship(
		Objects\IRelationshipObject $relationship,
		Objects\IResourceObjectCollection|null $included,
		Entities\Widgets\DataSources\DeviceProperty|null $entity,
	): DevicesEntities\Devices\Properties\Property
	{
		if (
			$relationship->getData() instanceof Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$property = $this->propertiesRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
			);

			if ($property !== null) {
				return $property;
			}
		}

		throw new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval(
				$this->translator->translate('//devices-module-ui-module-bridge.base.messages.invalidRelation.heading'),
			),
			strval(
				$this->translator->translate('//devices-module-ui-module-bridge.base.messages.invalidRelation.message'),
			),
			[
				'pointer' => '/data/relationships/' . Schemas\Widgets\DataSources\DeviceProperty::RELATIONSHIPS_PROPERTY . '/data/id',
			],
		);
	}

}
