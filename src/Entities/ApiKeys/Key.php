<?php declare(strict_types = 1);

/**
 * Key.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Entities\ApiKeys;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\MiniServer\Entities;
use FastyBird\MiniServer\Types;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_miniserver_api_keys",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="API access keys"
 *     }
 * )
 */
class Key extends Entities\Entity implements IKey
{

	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="key_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var string
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="key_name", length=50, nullable=FALSE)
	 */
	private string $name;

	/**
	 * @var string
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="key_key", length=150, nullable=FALSE)
	 */
	private string $key;

	/**
	 * @var Types\KeyStateType
	 *
	 * @Enum(class=Types\KeyStateType::class)
	 * @IPubDoctrine\Crud(is={"writable"})
	 * @ORM\Column(type="string_enum", name="key_state", length=10, nullable=FALSE, options={"default": "active"})
	 */
	private $state;

	/**
	 * @param string $name
	 * @param string $key
	 * @param Types\KeyStateType $state
	 * @param Uuid\UuidInterface|NULL $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		string $name,
		string $key,
		Types\KeyStateType $state,
		?Uuid\UuidInterface $id = null
	) {
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->state = $state;

		$this->name = $name;
		$this->key = $key;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setKey(string $key): void
	{
		$this->key = $key;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setState(Types\KeyStateType $state): void
	{
		$this->state = $state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getState(): Types\KeyStateType
	{
		return $this->state;
	}

}
