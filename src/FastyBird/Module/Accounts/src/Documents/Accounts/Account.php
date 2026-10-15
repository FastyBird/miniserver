<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Documents\Accounts;

use DateTimeInterface;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_map;

/**
 * Account document
 */
#[Documents\Mapping\Document(entity: Entities\Accounts\Account::class)]
#[Documents\Mapping\RoutingMap([
	Types\RoutingKey::ACCOUNT_DOCUMENT_REPORTED->value,
	Types\RoutingKey::ACCOUNT_DOCUMENT_CREATED->value,
	Types\RoutingKey::ACCOUNT_DOCUMENT_UPDATED->value,
	Types\RoutingKey::ACCOUNT_DOCUMENT_DELETED->value,
])]
final readonly class Account implements Documents\Document, ObjectMapper\MappedObject
{

	/**
	 * @param array<int, Uuid\UuidInterface> $children
	 */
	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('first_name')]
		private string $firstName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('last_name')]
		private string $lastName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $language,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\AccountState::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\AccountState::class),
		])]
		private Types\AccountState $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('middle_name')]
		private string|null $middleName = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $email = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private DateTimeInterface|null $registered = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('last_visit')]
		private DateTimeInterface|null $lastVisit = null,
		#[ObjectMapper\Rules\AnyOf([
			new Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private Uuid\UuidInterface|null $parent = null,
		#[ObjectMapper\Rules\ArrayOf(
			new Rules\UuidValue(),
		)]
		private array $children = [],
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function getMiddleName(): string|null
	{
		return $this->middleName;
	}

	public function getEmail(): string|null
	{
		return $this->email;
	}

	public function getState(): Types\AccountState
	{
		return $this->state;
	}

	public function getLanguage(): string
	{
		return $this->language;
	}

	public function getRegistered(): DateTimeInterface|null
	{
		return $this->registered;
	}

	public function getLastVisit(): DateTimeInterface|null
	{
		return $this->lastVisit;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
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
			'first_name' => $this->getFirstName(),
			'last_name' => $this->getLastName(),
			'middle_name' => $this->getMiddleName(),
			'email' => $this->getEmail(),
			'state' => $this->getState()->value,
			'language' => $this->getLanguage(),
			'registered' => $this->getRegistered()?->format(DateTimeInterface::ATOM),
			'last_visit' => $this->getLastVisit()?->format(DateTimeInterface::ATOM),
			'parent' => $this->getParent()?->toString(),
			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
		];
	}

}
