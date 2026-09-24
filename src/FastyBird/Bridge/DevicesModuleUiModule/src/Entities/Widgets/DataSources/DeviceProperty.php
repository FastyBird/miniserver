<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Entities\Widgets\DataSources;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Entities as UiEntities;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_ui_module_bridge_devices_data_sources',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Widget data source connection to device',
	],
)]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DeviceProperty extends Property
{

	public const TYPE = 'device-property';

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\ManyToOne(targetEntity: DevicesEntities\Devices\Properties\Property::class)]
	#[ORM\JoinColumn(
		name: 'data_source_property',
		referencedColumnName: 'property_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private DevicesEntities\Devices\Properties\Property $property;

	public function __construct(
		DevicesEntities\Devices\Properties\Property $property,
		UiEntities\Widgets\Widget $widget,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($widget, $id);

		$this->property = $property;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDevice(): DevicesEntities\Devices\Device
	{
		return $this->property->getDevice();
	}

	public function getProperty(): DevicesEntities\Devices\Properties\Property
	{
		return $this->property;
	}

	public function setProperty(DevicesEntities\Devices\Properties\Property $property): void
	{
		$this->property = $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->getId()->toString(),
		]);
	}

}
