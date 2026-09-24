<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.07.18
 */

namespace FastyBird\Module\Devices\Entities\Devices\Controls;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_devices_controls',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Devices controls',
	],
)]
#[ORM\Index(columns: ['control_name'], name: 'control_name_idx')]
#[ORM\UniqueConstraint(name: 'control_name_unique', columns: ['control_name', 'device_id'])]
class Control implements DevicesEntities\Entity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use DevicesEntities\TEntity;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'control_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'control_name', type: 'string', length: 100, nullable: false)]
	private string $name;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: DevicesEntities\Devices\Device::class,
		cascade: ['persist'],
		inversedBy: 'controls',
	)]
	#[ORM\JoinColumn(
		name: 'device_id',
		referencedColumnName: 'device_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private DevicesEntities\Devices\Device $device;

	public function __construct(string $name, DevicesEntities\Devices\Device $device)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->device = $device;

		$device->addControl($this);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDevice(): DevicesEntities\Devices\Device
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'name' => $this->getName(),

			'device' => $this->getDevice()->getId()->toString(),

			'owner' => $this->getDevice()->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
