<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Documents\Emails;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Email document
 */
#[Documents\Mapping\Document(entity: Entities\Emails\Email::class)]
#[Documents\Mapping\RoutingMap([
	Accounts\Types\RoutingKey::EMAIL_DOCUMENT_REPORTED->value,
	Accounts\Types\RoutingKey::EMAIL_DOCUMENT_CREATED->value,
	Accounts\Types\RoutingKey::EMAIL_DOCUMENT_UPDATED->value,
	Accounts\Types\RoutingKey::EMAIL_DOCUMENT_DELETED->value,
])]
final readonly class Email implements Documents\Document
{

	public function __construct(
		private Uuid\UuidInterface $id,
		private Uuid\UuidInterface $account,
		#[Documents\Mapping\RequirePattern('/^[\w\-\.]+@[\w\-\.]+\.[\w-]{2,63}$/')]
		private string $address,
		#[Documents\Mapping\MapBoolLike]
		private bool $default = false,
		#[Documents\Mapping\MapBoolLike]
		private bool $verified = false,
		#[Documents\Mapping\MapBoolLike]
		private bool $private = false,
		#[Documents\Mapping\MapBoolLike]
		private bool $public = false,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getAccount(): Uuid\UuidInterface
	{
		return $this->account;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public function isVerified(): bool
	{
		return $this->verified;
	}

	public function isPrivate(): bool
	{
		return $this->private;
	}

	public function isPublic(): bool
	{
		return $this->public;
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::ACCOUNTS;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'account' => $this->getAccount()->toString(),
			'address' => $this->getAddress(),
			'default' => $this->isDefault(),
			'verified' => $this->isVerified(),
			'private' => $this->isPrivate(),
			'public' => $this->isPublic(),
		];
	}

}
