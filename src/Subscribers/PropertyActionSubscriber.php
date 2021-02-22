<?php declare(strict_types = 1);

/**
 * PropertyActionSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.02.21
 */

namespace FastyBird\MiniServer\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\TriggersModule\Entities as TriggersModuleEntities;
use Nette;

/**
 * Trigger channel property action entity mapping overrides
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyActionSubscriber implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/**
	 * Register events
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::loadClassMetadata,
		];
	}

	/**
	 * @param ORM\Event\LoadClassMetadataEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function loadClassMetadata(ORM\Event\LoadClassMetadataEventArgs $eventArgs): void
	{
		$metadata = $eventArgs->getClassMetadata();

		if (
			class_implements($metadata->getName()) !== false
			&& in_array(
				TriggersModuleEntities\Actions\IChannelPropertyAction::class,
				class_implements($metadata->getName()),
				true
			)
		) {
			unset($metadata->fieldMappings['property']);

			if (!$metadata->hasAssociation('property')) {
				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Channels\Properties\Property::class,
					'fieldName'    => 'property',
					'joinColumns'  => [
						[
							'name'                 => 'action_channel_property',
							'referencedColumnName' => 'property_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}

			unset($metadata->fieldMappings['channel']);

			if (!$metadata->hasAssociation('channel')) {
				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Channels\Channel::class,
					'fieldName'    => 'channel',
					'joinColumns'  => [
						[
							'name'                 => 'action_channel',
							'referencedColumnName' => 'channel_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}

			unset($metadata->fieldMappings['device']);

			if (!$metadata->hasAssociation('device')) {
				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Devices\Device::class,
					'fieldName'    => 'device',
					'joinColumns'  => [
						[
							'name'                 => 'action_device',
							'referencedColumnName' => 'device_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}
		}
	}

}
