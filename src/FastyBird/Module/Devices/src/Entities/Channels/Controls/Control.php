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
 * @date           19.07.19
 */

namespace FastyBird\Module\Devices\Entities\Channels\Controls;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use FastyBird\Library\DoctrineTimestampable;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use Nette\Utils;
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
class Control implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'control_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'control_name', type: 'string', length: 100, nullable: false)]
	private string $name;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: Entities\Channels\Channel::class,
		cascade: ['persist'],
		inversedBy: 'controls',
	)]
	#[ORM\JoinColumn(
		name: 'channel_id',
		referencedColumnName: 'channel_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private Entities\Channels\Channel $channel;

	public function __construct(string $name, Entities\Channels\Channel $channel)
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

	public function getChannel(): Entities\Channels\Channel
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
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

	public function getSource(): MetadataTypes\Sources\Module
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
