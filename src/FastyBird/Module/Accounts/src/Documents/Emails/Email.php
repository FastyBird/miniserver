<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Documents\Emails;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use Orisai\ObjectMapper;
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
final readonly class Email implements Documents\Document, ObjectMapper\MappedObject
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $account,
		#[ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true)]
		private string $address,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $default = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $verified = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $private = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
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
