<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Entities\Policies;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Security\Types;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_security_policies',
	indexes: [
		new ORM\Index(columns: ['policy_type'], name: 'policy_type_idx'),
	],
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Casbin policies',
	],
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'policy_type', type: 'string', length: 100)]
// Seeded with this root itself. Policy is concrete and 'policy' is a persisted
// policy_type value, so the root has to be a key in its own map; subtypes such as the
// Accounts module's Role are contributed at runtime from #[DiscriminatorEntry].
//
// The map must be non-empty. ClassMetadataFactory calls addDefaultDiscriminatorMap() before
// dispatching loadClassMetadata and only when the map is empty, and that default keys every
// subtype by its short class name -- so each subtype would land in the map twice, once under
// the short name and once under its DiscriminatorEntry name. An explicit map skips the default
// entirely, which is what the removed doctrine/orm patch achieved by deferring the call.
#[ORM\DiscriminatorMap([Policy::TYPE => Policy::class])]
class Policy implements Entities\CrudEntity
{

	public const string TYPE = 'policy';

	#[ORM\Id]
	#[ORM\Column(name: 'policy_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(
		name: 'ptype',
		type: 'string',
		nullable: false,
		enumType: Types\PolicyType::class,
	)]
	protected Types\PolicyType $type;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v0', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v0 = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v1', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v1 = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v2', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v2 = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v3', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v3 = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v4', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v4 = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'v5', type: 'string', length: 150, nullable: true, options: ['default' => null])]
	protected string|null $v5 = null;

	public function __construct(
		Types\PolicyType $type,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->type = $type;

		$this->v0 = null;
		$this->v1 = null;
		$this->v2 = null;
		$this->v3 = null;
		$this->v4 = null;
		$this->v5 = null;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\PolicyType
	{
		return $this->type;
	}

	public function getV0(): string|null
	{
		return $this->v0;
	}

	public function setV0(string|null $v0): void
	{
		$this->v0 = $v0;
	}

	public function getV1(): string|null
	{
		return $this->v1;
	}

	public function setV1(string|null $v1): void
	{
		$this->v1 = $v1;
	}

	public function getV2(): string|null
	{
		return $this->v2;
	}

	public function setV2(string|null $v2): void
	{
		$this->v2 = $v2;
	}

	public function getV3(): string|null
	{
		return $this->v3;
	}

	public function setV3(string|null $v3): void
	{
		$this->v3 = $v3;
	}

	public function getV4(): string|null
	{
		return $this->v4;
	}

	public function setV4(string|null $v4): void
	{
		$this->v4 = $v4;
	}

	public function getV5(): string|null
	{
		return $this->v5;
	}

	public function setV5(string|null $v5): void
	{
		$this->v5 = $v5;
	}

	/**
	 * @return array<string, string|null>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id->toString(),
			'type' => $this->type->value,
			'v0' => $this->v0,
			'v1' => $this->v1,
			'v2' => $this->v2,
			'v3' => $this->v3,
			'v4' => $this->v4,
			'v5' => $this->v5,
		];
	}

}
