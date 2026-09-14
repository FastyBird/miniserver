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
use FastyBird\Library\DoctrineCrud;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use FastyBird\Library\DoctrineTimestampable;
use FastyBird\Plugin\ApiKey\Entities;
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
class Key implements Entities\Entity, DoctrineCrud\Entities\IEntity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'key_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'key_name', type: 'string', length: 50, nullable: false)]
	private string $name;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'key_key', type: 'string', length: 150, nullable: false)]
	private string $key;

	#[IPubDoctrine\Crud(writable: true)]
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
