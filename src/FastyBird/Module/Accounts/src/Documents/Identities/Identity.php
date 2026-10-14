<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Documents\Identities;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Identity entity
 */
#[Documents\Mapping\Document(entity: Entities\Identities\Identity::class)]
#[Documents\Mapping\RoutingMap([
	Types\RoutingKey::IDENTITY_DOCUMENT_REPORTED->value,
	Types\RoutingKey::IDENTITY_DOCUMENT_CREATED->value,
	Types\RoutingKey::IDENTITY_DOCUMENT_UPDATED->value,
	Types\RoutingKey::IDENTITY_DOCUMENT_DELETED->value,
])]
final readonly class Identity implements Documents\Document
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $account,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\IdentityState::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\IdentityState::class),
		])]
		private Types\IdentityState $state,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $uid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $hash = null,
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

	public function getState(): Types\IdentityState
	{
		return $this->state;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getHash(): string|null
	{
		return $this->hash;
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
			'state' => $this->getState()->value,
			'uid' => $this->getUid(),
			'hash' => $this->getHash(),
		];
	}

}
