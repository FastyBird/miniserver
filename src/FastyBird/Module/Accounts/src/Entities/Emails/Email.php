<?php declare(strict_types = 1);

/**
 * Email.php
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

namespace FastyBird\Module\Accounts\Entities\Emails;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Types;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_accounts_module_emails',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Emails addresses',
	],
)]
#[ORM\Index(columns: ['email_address'], name: 'email_address_idx')]
#[ORM\UniqueConstraint(name: 'email_address_unique', columns: ['email_address'])]
class Email implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated,
	DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'email_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\ManyToOne(
		targetEntity: Entities\Accounts\Account::class,
		inversedBy: 'emails',
	)]
	#[ORM\JoinColumn(
		name: 'account_id',
		referencedColumnName: 'account_id',
		nullable: false,
		onDelete: 'CASCADE',
	)]
	private Entities\Accounts\Account $account;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'email_address', type: 'string', length: 150, nullable: false)]
	private string $address;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'email_default', type: 'boolean', length: 1, nullable: false, options: ['default' => false])]
	private bool $default = false;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'email_verified', type: 'boolean', length: 1, nullable: false, options: ['default' => false])]
	private bool $verified = false;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'email_verification_hash',
		type: 'string',
		length: 150,
		nullable: true,
		options: ['default' => null],
	)]
	private string|null $verificationHash = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'email_verification_created', type: 'datetime', nullable: true, options: ['default' => null])]
	private DateTimeInterface|null $verificationCreated = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'email_verification_completed', type: 'datetime', nullable: true, options: ['default' => null])]
	private DateTimeInterface|null $verificationCompleted = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'email_visibility',
		type: 'string',
		nullable: false,
		enumType: Types\EmailVisibility::class,
		options: ['default' => Types\EmailVisibility::PUBLIC],
	)]
	private Types\EmailVisibility $visibility;

	/**
	 * @throws Exceptions\EmailIsNotValid
	 */
	public function __construct(
		Entities\Accounts\Account $account,
		string $address,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->account = $account;
		$this->visibility = Types\EmailVisibility::PUBLIC;

		$this->setAddress($address);

		$account->addEmail($this);
	}

	public function getVerificationHash(): string|null
	{
		return $this->verificationHash;
	}

	public function setVerificationHash(string $verificationHash): void
	{
		$this->verificationHash = $verificationHash;
	}

	public function getVerificationCreated(): DateTimeInterface|null
	{
		return $this->verificationCreated;
	}

	public function setVerificationCreated(DateTimeInterface $verificationCreated): void
	{
		$this->verificationCreated = $verificationCreated;
	}

	public function getVerificationCompleted(): DateTimeInterface|null
	{
		return $this->verificationCompleted;
	}

	public function setVerificationCompleted(DateTimeInterface|null $verificationCompleted = null): void
	{
		$this->verificationCompleted = $verificationCompleted;
	}

	public function getAccount(): Entities\Accounts\Account
	{
		return $this->account;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	/**
	 * @throws Exceptions\EmailIsNotValid
	 */
	public function setAddress(string $address): void
	{
		if (!Utils\Validators::isEmail($address)) {
			throw new Exceptions\EmailIsNotValid('Invalid email address given');
		}

		$this->address = Utils\Strings::lower($address);
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public function setDefault(bool $default): void
	{
		$this->default = $default;
	}

	public function isVerified(): bool
	{
		return $this->verified;
	}

	public function setVerified(bool $verified): void
	{
		$this->verified = $verified;
	}

	public function isPrivate(): bool
	{
		return $this->getVisibility() === Types\EmailVisibility::PRIVATE;
	}

	public function getVisibility(): Types\EmailVisibility
	{
		return $this->visibility;
	}

	public function setVisibility(Types\EmailVisibility $visibility): void
	{
		$this->visibility = $visibility;
	}

	public function isPublic(): bool
	{
		return $this->getVisibility() === Types\EmailVisibility::PUBLIC;
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
			'address' => $this->getAddress(),
			'default' => $this->isDefault(),
			'verified' => $this->isVerified(),
			'private' => $this->isPrivate(),
			'public' => $this->isPublic(),
		];
	}

	public function __toString(): string
	{
		return $this->address;
	}

}
