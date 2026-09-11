<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Entities\Identities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Types;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function strval;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_accounts_module_identities',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Accounts identities',
	],
)]
#[ORM\Index(columns: ['identity_uid'], name: 'identity_uid_idx')]
#[ORM\Index(columns: ['identity_state'], name: 'identity_state_idx')]
#[ORM\UniqueConstraint(name: 'identity_uid_unique', columns: ['identity_uid'])]
class Identity implements Entities\Entity,
	SimpleAuthSecurity\IIdentity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'identity_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: Entities\Accounts\Account::class,
		cascade: ['persist', 'remove'],
		inversedBy: 'identities',
	)]
	#[ORM\JoinColumn(
		name: 'account_id',
		referencedColumnName: 'account_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	protected Entities\Accounts\Account $account;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'identity_uid', type: 'string', length: 50, nullable: false)]
	protected string $uid;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'identity_token', type: 'string', nullable: false)]
	protected string $password;

	protected string|null $plainPassword = null;

	#[IPubDoctrine\Crud(writable: true)]
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
		Entities\Accounts\Account $account,
		string $uid,
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
	public function verifyPassword(string $rawPassword): bool
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
	public function setPassword(string|Helpers\Password $password): void
	{
		if ($password instanceof Helpers\Password) {
			$this->password = $password->getHash();

		} else {
			$password = Helpers\Password::createFromString($password);

			$this->password = $password->getHash();
			$this->plainPassword = $password->getPassword();
		}

		$this->setSalt($password->getSalt());
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
	public function getRoles(): array
	{
		return [];
	}

	public function invalidate(): void
	{
		$this->state = Types\IdentityState::INVALID;
	}

	public function getAccount(): Entities\Accounts\Account
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
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
