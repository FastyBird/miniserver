<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Entities\ApiKeys;

use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Security\Types;
use Ramsey\Uuid;
use function date_default_timezone_get;

/**
 * A credential for a machine client. It belongs to an account and authenticates as that account.
 *
 * Only the SHA-256 hash of the key's secret is stored (Identity\ApiKeySecret); the secret itself is
 * shown once, when the key is created, and cannot be read back.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fb_security_api_keys')]
#[ORM\Index(columns: ['account_id'], name: 'key_account_idx')]
#[ORM\UniqueConstraint(name: 'key_hash_unique', columns: ['key_hash'])]
class ApiKey implements Entities\CrudEntity, Entities\EntityCreated, Entities\EntityUpdated
{

	use Entities\HasEntityCreated;
	use Entities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'key_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'account_id', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: false)]
	protected Uuid\UuidInterface $account;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'key_name', type: 'string', length: 100, nullable: false)]
	protected string $name;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'key_hash', type: 'string', length: 64, nullable: false)]
	protected string $hash;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'key_state',
		type: 'string',
		nullable: false,
		enumType: Types\ApiKeyState::class,
		options: ['default' => Types\ApiKeyState::ACTIVE],
	)]
	protected Types\ApiKeyState $state;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'expires_at', type: 'datetime_immutable', nullable: true)]
	protected DateTimeImmutable|null $expiresAt = null;

	/**
	 * @throws DateInvalidTimeZoneException
	 */
	public function __construct(
		Uuid\UuidInterface $account,
		string $name,
		string $hash,
		DateTimeImmutable|null $expiresAt = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->account = $account;
		$this->name = $name;
		$this->hash = $hash;
		$this->expiresAt = self::storable($expiresAt);

		$this->state = Types\ApiKeyState::ACTIVE;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	/**
	 * The account the key authenticates as, which is also the Casbin subject
	 */
	public function getAccount(): Uuid\UuidInterface
	{
		return $this->account;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * The SHA-256 hash of the secret, as lowercase hex
	 */
	public function getHash(): string
	{
		return $this->hash;
	}

	public function getState(): Types\ApiKeyState
	{
		return $this->state;
	}

	public function setState(Types\ApiKeyState $state): void
	{
		$this->state = $state;
	}

	public function getExpiresAt(): DateTimeImmutable|null
	{
		return $this->expiresAt;
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 */
	public function setExpiresAt(DateTimeImmutable|null $expiresAt): void
	{
		$this->expiresAt = self::storable($expiresAt);
	}

	public function isActive(): bool
	{
		return $this->state === Types\ApiKeyState::ACTIVE;
	}

	/**
	 * A key expires at its expiry instant: from that moment on it no longer authenticates
	 */
	public function isExpired(DateTimeInterface $now): bool
	{
		return $this->expiresAt !== null && $this->expiresAt <= $now;
	}

	/**
	 * A DATETIME column keeps the wall-clock time and drops the offset, and Doctrine reads it back
	 * in PHP's default time zone. The instant is therefore stored in that zone, or an expiry given
	 * in any other offset would move by the difference.
	 *
	 * @throws DateInvalidTimeZoneException
	 */
	private static function storable(DateTimeImmutable|null $instant): DateTimeImmutable|null
	{
		return $instant?->setTimezone(new DateTimeZone(date_default_timezone_get()));
	}

}
