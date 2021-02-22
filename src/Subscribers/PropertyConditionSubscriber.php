<?php declare(strict_types = 1);

/**
 * PropertyConditionSubscriber.php
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
 * Trigger device or channel property condition entity mapping overrides
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyConditionSubscriber implements Common\EventSubscriber
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
				TriggersModuleEntities\Conditions\IChannelPropertyCondition::class,
				class_implements($metadata->getName()),
				true
			)
		) {
			if (!$metadata->hasAssociation('property')) {
				unset($metadata->fieldMappings['property']);

				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Channels\Properties\Property::class,
					'fieldName'    => 'property',
					'joinColumns'  => [
						[
							'name'                 => 'condition_channel_property',
							'referencedColumnName' => 'property_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}

			if (!$metadata->hasAssociation('channel')) {
				unset($metadata->fieldMappings['channel']);

				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Channels\Channel::class,
					'fieldName'    => 'channel',
					'joinColumns'  => [
						[
							'name'                 => 'condition_channel',
							'referencedColumnName' => 'channel_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}
		}

		if (
			class_implements($metadata->getName()) !== false
			&& in_array(
				TriggersModuleEntities\Conditions\IDevicePropertyCondition::class,
				class_implements($metadata->getName()),
				true
			)
		) {
			if (!$metadata->hasAssociation('property')) {
				unset($metadata->fieldMappings['property']);

				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Devices\Properties\Property::class,
					'fieldName'    => 'property',
					'joinColumns'  => [
						[
							'name'                 => 'condition_device_property',
							'referencedColumnName' => 'property_key',
							'onDelete'             => 'CASCADE',
							'nullable'             => true,
						],
					],
				]);
			}
		}

		if (
			class_implements($metadata->getName()) !== false
			&& in_array(
				TriggersModuleEntities\Conditions\IChannelPropertyCondition::class,
				class_implements($metadata->getName()),
				true
			)
		) {
			if (!$metadata->hasAssociation('device')) {
				unset($metadata->fieldMappings['device']);

				$metadata->mapManyToOne([
					'targetEntity' => DevicesModuleEntities\Devices\Device::class,
					'fieldName'    => 'device',
					'joinColumns'  => [
						[
							'name'                 => 'condition_device',
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
