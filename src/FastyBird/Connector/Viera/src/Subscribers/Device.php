<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           27.08.24
 */

namespace FastyBird\Connector\Viera\Subscribers;

use Doctrine\DBAL;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Types as VieraTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * Device events
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Helpers\ChannelProperty $channelProperty,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\EntityUpdated::class => 'updated',
		];
	}

	/**
	 * @throws DBAL\Exception
	 * @throws VieraExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function updated(DevicesEvents\EntityUpdated $event): void
	{
		$entity = $event->getEntity();

		if (!$entity instanceof Entities\Devices\Device) {
			return;
		}

		$this->checkChannelProperties($entity);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws VieraExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function checkChannelProperties(Entities\Devices\Device $device): void
	{
		$findChannelQuery = new Queries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VieraTypes\ChannelType::TELEVISION);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

		if ($channel === null) {
			return;
		}

		$this->channelProperty->create(
			DevicesEntities\Channels\Properties\Dynamic::class,
			$channel->getId(),
			null,
			ValuesTypes\DataType::BOOLEAN,
			VieraTypes\ChannelPropertyIdentifier::STATE,
			DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::STATE->value),
			null,
			true,
			true,
		);

		$this->channelProperty->create(
			DevicesEntities\Channels\Properties\Dynamic::class,
			$channel->getId(),
			null,
			ValuesTypes\DataType::UCHAR,
			VieraTypes\ChannelPropertyIdentifier::VOLUME,
			DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::VOLUME->value),
			[
				0,
				100,
			],
			true,
			true,
		);

		$this->channelProperty->create(
			DevicesEntities\Channels\Properties\Dynamic::class,
			$channel->getId(),
			null,
			ValuesTypes\DataType::BOOLEAN,
			VieraTypes\ChannelPropertyIdentifier::MUTE,
			DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::MUTE->value),
			null,
			true,
			true,
		);

		$this->channelProperty->create(
			DevicesEntities\Channels\Properties\Dynamic::class,
			$channel->getId(),
			null,
			ValuesTypes\DataType::STRING,
			VieraTypes\ChannelPropertyIdentifier::REMOTE,
			DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::REMOTE->value),
			null,
			true,
		);

		$findChannelProperty = new Queries\Entities\FindChannelProperties();
		$findChannelProperty->forChannel($channel);
		$findChannelProperty->byIdentifier(VieraTypes\ChannelPropertyIdentifier::HDMI);

		$hdmiProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelProperty,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		if ($hdmiProperty === null) {
			$this->channelProperty->create(
				DevicesEntities\Channels\Properties\Dynamic::class,
				$channel->getId(),
				null,
				ValuesTypes\DataType::ENUM,
				VieraTypes\ChannelPropertyIdentifier::HDMI,
				DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::HDMI->value),
				null,
				true,
			);
		}

		$findChannelProperty = new Queries\Entities\FindChannelProperties();
		$findChannelProperty->forChannel($channel);
		$findChannelProperty->byIdentifier(VieraTypes\ChannelPropertyIdentifier::APPLICATION);

		$appsProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelProperty,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		if ($appsProperty === null) {
			$this->channelProperty->create(
				DevicesEntities\Channels\Properties\Dynamic::class,
				$channel->getId(),
				null,
				ValuesTypes\DataType::ENUM,
				VieraTypes\ChannelPropertyIdentifier::APPLICATION,
				DevicesUtilities\Name::createName(VieraTypes\ChannelPropertyIdentifier::APPLICATION->value),
				null,
				true,
			);
		}

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

}
