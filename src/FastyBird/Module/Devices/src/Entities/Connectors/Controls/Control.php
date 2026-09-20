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
 * @date           16.04.21
 */

namespace FastyBird\Module\Devices\Entities\Connectors\Controls;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\DoctrineTimestampable;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_devices_module_connectors_controls',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Connectors controls',
	],
)]
#[ORM\Index(columns: ['control_name'], name: 'control_name_idx')]
#[ORM\UniqueConstraint(name: 'control_name_unique', columns: ['control_name', 'connector_id'])]
class Control implements Entities\Entity,
	DoctrineTimestampable\IEntityCreated, DoctrineTimestampable\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\TEntityCreated;
	use DoctrineTimestampable\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'control_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'control_name', type: 'string', length: 100, nullable: false)]
	private string $name;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: Entities\Connectors\Connector::class,
		cascade: ['persist'],
		inversedBy: 'controls',
	)]
	#[ORM\JoinColumn(
		name: 'connector_id',
		referencedColumnName: 'connector_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private Entities\Connectors\Connector $connector;

	public function __construct(string $name, Entities\Connectors\Connector $connector)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->connector = $connector;

		$connector->addControl($this);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		return $this->connector;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'name' => $this->getName(),

			'connector' => $this->getConnector()->getId()->toString(),

			'owner' => $this->getConnector()->getOwnerId(),
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
