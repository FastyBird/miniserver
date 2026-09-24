<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           21.06.23
 */

namespace FastyBird\Connector\Viera\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Types as VieraTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as DoctrineCrudExceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Helpers\ChannelProperty $channelProperty,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Devices\Device) {
			$this->configureDeviceState($entity);
		}

		// Check for valid entity
		if (
			$entity instanceof DevicesEntities\Channels\Channel
			&& $entity->getDevice() instanceof Entities\Devices\Device
		) {
			$this->configureDeviceKeys($entity);
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		if (
			$entity instanceof DevicesEntities\Channels\Channel
			&& $entity->getDevice() instanceof Entities\Devices\Device
		) {
			$this->configureDeviceKeys($entity);
		}

		if (
			$entity instanceof DevicesEntities\Channels\Properties\Dynamic
			&& $entity->getChannel()->getDevice() instanceof Entities\Devices\Device
			&& (
				$entity->getIdentifier() === VieraTypes\ChannelPropertyIdentifier::HDMI->value
				|| $entity->getIdentifier() === VieraTypes\ChannelPropertyIdentifier::APPLICATION->value
			)
		) {
			$this->configureDeviceInputSource($entity);
		}
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function configureDeviceState(Entities\Devices\Device $device): void
	{
		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(VieraTypes\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicesPropertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => ValuesTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => VieraTypes\DevicePropertyIdentifier::STATE->value,
				'name' => DevicesUtilities\Name::createName(VieraTypes\DevicePropertyIdentifier::STATE->value),
				'dataType' => ValuesTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function configureDeviceKeys(DevicesEntities\Channels\Channel $channel): void
	{
		foreach (Viera\Constants::KEYS_PROPERTIES as $actionKey => $identifier) {
			$this->channelProperty->create(
				DevicesEntities\Channels\Properties\Dynamic::class,
				$channel->getId(),
				null,
				ValuesTypes\DataType::BUTTON,
				$identifier,
				DevicesUtilities\Name::createName($identifier->value),
				[
					[
						Payloads\Button::CLICKED->value,
						VieraTypes\ActionKey::from($actionKey)->value,
						VieraTypes\ActionKey::from($actionKey)->value,
					],
				],
				true,
			);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function configureDeviceInputSource(DevicesEntities\Channels\Properties\Dynamic $property): void
	{
		$channel = $property->getChannel();

		if ($property->getIdentifier() === VieraTypes\ChannelPropertyIdentifier::HDMI->value) {
			$hdmiFormat = $property->getFormat();

			$hdmiFormat = $hdmiFormat instanceof Formats\CombinedEnum
				? $hdmiFormat->toArray()
				: [];
		} else {
			$findChannelProperty = new Queries\Entities\FindChannelProperties();
			$findChannelProperty->forChannel($channel);
			$findChannelProperty->byIdentifier(VieraTypes\ChannelPropertyIdentifier::HDMI);

			$hdmiProperty = $this->channelsPropertiesRepository->findOneBy(
				$findChannelProperty,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			$hdmiFormat = $hdmiProperty?->getFormat();

			$hdmiFormat = $hdmiFormat instanceof Formats\CombinedEnum
				? $hdmiFormat->toArray()
				: [];
		}

		if ($property->getIdentifier() === VieraTypes\ChannelPropertyIdentifier::APPLICATION->value) {
			$applicationFormat = $property->getFormat();

			$applicationFormat = $applicationFormat instanceof Formats\CombinedEnum
				? $applicationFormat->toArray()
				: [];
		} else {
			$findChannelProperty = new Queries\Entities\FindChannelProperties();
			$findChannelProperty->forChannel($channel);
			$findChannelProperty->byIdentifier(VieraTypes\ChannelPropertyIdentifier::APPLICATION);

			$applicationProperty = $this->channelsPropertiesRepository->findOneBy(
				$findChannelProperty,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			$applicationFormat = $applicationProperty?->getFormat();

			$applicationFormat = $applicationFormat instanceof Formats\CombinedEnum
				? $applicationFormat->toArray()
				: [];
		}

		$this->channelProperty->create(
			DevicesEntities\Channels\Properties\Dynamic::class,
			$channel->getId(),
			null,
			ValuesTypes\DataType::ENUM,
			VieraTypes\ChannelPropertyIdentifier::INPUT_SOURCE,
			DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::INPUT_SOURCE->value),
			array_merge(
				[
					[
						Viera\Constants::TV_IDENTIFIER,
						Viera\Constants::TV_CODE,
						Viera\Constants::TV_CODE,
					],
				],
				$hdmiFormat,
				$applicationFormat,
			),
			true,
		);
	}

}
