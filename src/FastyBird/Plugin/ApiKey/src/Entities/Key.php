<?php declare(strict_types = 1);

/**
 * Key.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Plugin\ApiKey\Entities as ApiKeyEntities;
use FastyBird\Plugin\ApiKey\Types;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_api_key_plugin_keys',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'API Key plugin access keys',
	],
)]
class Key implements ApiKeyEntities\Entity, PersistenceEntities\CrudEntity,
	PersistenceEntities\EntityCreated, PersistenceEntities\EntityUpdated
{

	use TEntity;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'key_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'key_name', type: 'string', length: 50, nullable: false)]
	private string $name;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'key_key', type: 'string', length: 150, nullable: false)]
	private string $key;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'key_state',
		type: 'string',
		length: 10,
		nullable: false,
		enumType: Types\KeyState::class,
		options: ['default' => Types\KeyState::ACTIVE],
	)]
	private Types\KeyState $state;

	public function __construct(
		string $name,
		string $key,
		Types\KeyState $state,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->state = $state;

		$this->name = $name;
		$this->key = $key;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setKey(string $key): void
	{
		$this->key = $key;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function setState(Types\KeyState $state): void
	{
		$this->state = $state;
	}

	public function getState(): Types\KeyState
	{
		return $this->state;
	}

}
