<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit\Entities\Clients;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use Ramsey\Uuid;
use function is_resource;
use function rewind;
use function stream_get_contents;
use function strval;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_homekit_connector_clients',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'HomeKit connector clients',
	],
)]
#[ORM\UniqueConstraint(name: 'client_uid_unique', columns: ['client_uid', 'connector_id'])]
class Client implements PersistenceEntities\CrudEntity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'client_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: HomeKitEntities\Connectors\Connector::class,
		inversedBy: 'clients',
	)]
	#[ORM\JoinColumn(
		name: 'connector_id',
		referencedColumnName: 'connector_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private HomeKitEntities\Connectors\Connector $connector;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'client_uid', type: 'string', nullable: false, length: 255)]
	private string $uid;

	/** @var string|resource */
	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'client_public_key', type: 'binary', length: 255, nullable: false)]
	private $publicKey;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'client_admin', type: 'boolean', nullable: false, options: ['default' => true])]
	private bool $admin = true;

	public function __construct(
		string $uid,
		string $publicKey,
		HomeKitEntities\Connectors\Connector $connector,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->uid = $uid;
		$this->publicKey = $publicKey;

		$this->connector = $connector;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getConnector(): HomeKitEntities\Connectors\Connector
	{
		return $this->connector;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getPublicKey(): string
	{
		if (is_resource($this->publicKey)) {
			rewind($this->publicKey);

			return strval(stream_get_contents($this->publicKey));
		}

		return strval($this->publicKey);
	}

	public function setPublicKey(string $publicKey): void
	{
		$this->publicKey = $publicKey;
	}

	public function isAdmin(): bool
	{
		return $this->admin;
	}

	public function setAdmin(bool $admin): void
	{
		$this->admin = $admin;
	}

}
