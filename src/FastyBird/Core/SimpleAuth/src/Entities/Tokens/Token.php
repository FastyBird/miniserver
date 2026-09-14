<?php declare(strict_types = 1);

/**
 * Token.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Core\SimpleAuth\Entities\Tokens;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\SimpleAuth\Types;
use FastyBird\Library\DoctrineCrud;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_security_tokens',
	indexes: [
		new ORM\Index(columns: ['token_state'], name: 'token_state_idx'),
	],
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'name' => 'Security tokens',
	],
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'token_type', type: 'string', length: 100)]
// Seeded with this root itself, which is exactly what Core\Application's EntityDiscriminator
// appends when the root is absent from its own map; the concrete subtypes are contributed at
// runtime from #[DiscriminatorEntry] in the packages that own them.
//
// The map must be non-empty. ClassMetadataFactory calls addDefaultDiscriminatorMap() before
// dispatching loadClassMetadata and only when the map is empty, and that default keys every
// subtype by its short class name -- so each subtype would land in the map twice, once under
// the short name and once under its DiscriminatorEntry name. An explicit map skips the default
// entirely, which is what the removed doctrine/orm patch achieved by deferring the call.
#[ORM\DiscriminatorMap(['token' => Token::class])]
#[ORM\MappedSuperclass]
abstract class Token implements DoctrineCrud\Entities\IEntity
{

	#[ORM\Id]
	#[ORM\Column(type: Uuid\Doctrine\UuidBinaryType::NAME, name: 'token_id')]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
	#[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'token_id', nullable: true, onDelete: 'set null')]
	protected self|null $parent = null;

	/** @var Common\Collections\Collection<int, Token> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
	protected Common\Collections\Collection $children;

	#[ORM\Column(name: 'token_token', type: 'text', nullable: false)]
	protected string $token;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'token_state',
		type: 'string',
		nullable: false,
		enumType: Types\TokenState::class,
		options: ['default' => Types\TokenState::ACTIVE],
	)]
	protected Types\TokenState $state;

	public function __construct(string $token, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->token = $token;
		$this->state = Types\TokenState::ACTIVE;

		$this->children = new Common\Collections\ArrayCollection();
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getParent(): self|null
	{
		return $this->parent;
	}

	public function setParent(self $token): void
	{
		$this->parent = $token;

		$token->addChild($this);
	}

	public function addChild(self $child): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->children->contains($child)) {
			// ...and assign it to collection
			$this->children->add($child);
		}
	}

	public function removeParent(): void
	{
		$this->parent = null;
	}

	/**
	 * @return array<Token>
	 */
	public function getChildren(): array
	{
		return $this->children->toArray();
	}

	/**
	 * @param array<Token> $children
	 */
	public function setChildren(array $children): void
	{
		$this->children = new Common\Collections\ArrayCollection();

		foreach ($children as $entity) {
			// ...and assign them to collection
			$this->children->add($entity);
		}
	}

	public function removeChild(self $child): void
	{
		// Check if collection contain removing entity...
		if ($this->children->contains($child)) {
			// ...and remove it from collection
			$this->children->removeElement($child);
		}
	}

	public function getState(): Types\TokenState
	{
		return $this->state;
	}

	public function setState(Types\TokenState $state): void
	{
		$this->state = $state;
	}

	public function isActive(): bool
	{
		return $this->state === Types\TokenState::ACTIVE;
	}

	public function isBlocked(): bool
	{
		return $this->state === Types\TokenState::BLOCKED;
	}

	public function isDeleted(): bool
	{
		return $this->state === Types\TokenState::DELETED;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function __toString(): string
	{
		return $this->getToken();
	}

}
