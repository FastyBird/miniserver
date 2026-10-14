<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Entities\Identities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Entities as PersistenceEntities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Security\Identity as SecurityIdentity;
use FastyBird\Module\Accounts\Entities as AccountsEntities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Types;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use SensitiveParameter;
use function strval;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_accounts_module_identities',
	options: [
		'comment' => 'Accounts identities',
	],
)]
#[ORM\Index(columns: ['identity_uid'], name: 'identity_uid_idx')]
#[ORM\Index(columns: ['identity_state'], name: 'identity_state_idx')]
#[ORM\UniqueConstraint(name: 'identity_uid_unique', columns: ['identity_uid'])]
class Identity implements AccountsEntities\Entity,
	SecurityIdentity\UserIdentity,
	AccountsEntities\EntityParams,
	PersistenceEntities\EntityCreated,
	PersistenceEntities\EntityUpdated
{

	use AccountsEntities\HasEntityId;
	use AccountsEntities\HasEntityParams;
	use PersistenceEntities\HasEntityCreated;
	use PersistenceEntities\HasEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'identity_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: AccountsEntities\Accounts\Account::class,
		cascade: ['persist', 'remove'],
		inversedBy: 'identities',
	)]
	#[ORM\JoinColumn(
		name: 'account_id',
		referencedColumnName: 'account_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	protected AccountsEntities\Accounts\Account $account;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'identity_uid', type: 'string', length: 50, nullable: false)]
	protected string $uid;

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'identity_token', type: 'string', nullable: false)]
	protected string $password;

	protected string|null $plainPassword = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'identity_state',
		type: 'string',
		nullable: false,
		enumType: Types\IdentityState::class,
		options: ['default' => Types\IdentityState::ACTIVE],
	)]
	protected Types\IdentityState $state;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		AccountsEntities\Accounts\Account $account,
		string $uid,
		#[SensitiveParameter]
		string $password,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->account = $account;
		$this->uid = $uid;

		$this->state = Types\IdentityState::ACTIVE;

		$this->setPassword($password);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function verifyPassword(
		#[SensitiveParameter]
		string $rawPassword,
	): bool
	{
		return $this->getPassword()
			->isEqual($rawPassword, $this->getSalt());
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPassword(): Helpers\Password
	{
		return $this->plainPassword !== null
			? new Helpers\Password(null, $this->plainPassword, $this->getSalt())
			: new Helpers\Password($this->password, null, $this->getSalt());
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setPassword(
		#[SensitiveParameter]
		string|Helpers\Password $password,
	): void
	{
		if ($password instanceof Helpers\Password) {
			$this->password = $password->getHash();

		} else {
			$password = Helpers\Password::createFromString($password);

			$this->password = $password->getHash();
			$this->plainPassword = $password->getPassword();
		}

		$this->setSalt($password->salt);
	}

	public function getSalt(): string|null
	{
		$salt = $this->getParam('salt');

		return $salt === null ? null : strval($salt);
	}

	public function setSalt(string $salt): void
	{
		$this->setParam('salt', $salt);
	}

	public function isActive(): bool
	{
		return $this->state === Types\IdentityState::ACTIVE;
	}

	public function isBlocked(): bool
	{
		return $this->state === Types\IdentityState::BLOCKED;
	}

	public function isDeleted(): bool
	{
		return $this->state === Types\IdentityState::DELETED;
	}

	public function isInvalid(): bool
	{
		return $this->state === Types\IdentityState::INVALID;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getRoles(): array
	{
		return [];
	}

	public function invalidate(): void
	{
		$this->state = Types\IdentityState::INVALID;
	}

	public function getAccount(): AccountsEntities\Accounts\Account
	{
		return $this->account;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getState(): Types\IdentityState
	{
		return $this->state;
	}

	public function setState(Types\IdentityState $state): void
	{
		$this->state = $state;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'account' => $this->getAccount()->getId()->toString(),
			'uid' => $this->getUid(),
			'state' => $this->getState()->value,
		];
	}

	/**
	 * @return void
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	 */
	public function __clone()
	{
		$this->id = Uuid\Uuid::uuid4();
		$this->createdAt = new Utils\DateTime();
		$this->state = Types\IdentityState::ACTIVE;
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
