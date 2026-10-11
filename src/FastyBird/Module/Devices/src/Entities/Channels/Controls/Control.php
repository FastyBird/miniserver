<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Channels\Controls;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette\Utils;
use Override;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_channels_controls',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Device channels controls',
	],
)]
#[ORM\Index(columns: ['control_name'], name: 'control_name_idx')]
#[ORM\UniqueConstraint(name: 'control_name_unique', columns: ['control_name', 'channel_id'])]
class Control implements DevicesEntities\Entity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use DevicesEntities\HasEntityId;
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
		targetEntity: DevicesEntities\Channels\Channel::class,
		cascade: ['persist'],
		inversedBy: 'controls',
	)]
	#[ORM\JoinColumn(
		name: 'channel_id',
		referencedColumnName: 'channel_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private DevicesEntities\Channels\Channel $channel;

	public function __construct(string $name, DevicesEntities\Channels\Channel $channel)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->channel = $channel;

		$channel->addControl($this);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getChannel(): DevicesEntities\Channels\Channel
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'name' => $this->getName(),

			'channel' => $this->getChannel()->getId()->toString(),

			'owner' => $this->getChannel()->getDevice()->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	#[Override]
	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

	/**
	 * @throws Utils\JsonException
	 */
	#[Override]
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
