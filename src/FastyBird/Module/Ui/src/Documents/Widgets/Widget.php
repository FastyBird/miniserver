<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_map;

/**
 * Widget document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Widget::class)]
#[CoreDocuments\Mapping\InheritanceType('SINGLE_TABLE')]
#[CoreDocuments\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[CoreDocuments\Mapping\MappedSuperclass]
#[CoreDocuments\Mapping\RoutingMap([
	Ui\Types\RoutingKey::WIDGET_DOCUMENT_REPORTED->value,
	Ui\Types\RoutingKey::WIDGET_DOCUMENT_CREATED->value,
	Ui\Types\RoutingKey::WIDGET_DOCUMENT_UPDATED->value,
	Ui\Types\RoutingKey::WIDGET_DOCUMENT_DELETED->value,
])]
abstract class Widget implements UiDocuments\Document, CoreDocuments\Owner, CoreDocuments\CreatedAt, CoreDocuments\UpdatedAt
{

	use CoreDocuments\HasOwner;
	use CoreDocuments\HasCreatedAt;
	use CoreDocuments\HasUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $dataSources
	 * @param array<Uuid\UuidInterface> $tabs
	 * @param array<Uuid\UuidInterface> $groups
	 */
	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $display,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name = null,
		#[ObjectMapper\Rules\ArrayOf(
			new Rules\UuidValue(),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_sources')]
		private readonly array $dataSources = [],
		#[ObjectMapper\Rules\ArrayOf(
			new Rules\UuidValue(),
		)]
		private readonly array $tabs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new Rules\UuidValue(),
		)]
		private readonly array $groups = [],
		#[ObjectMapper\Rules\AnyOf([
			new Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getDisplay(): Uuid\UuidInterface
	{
		return $this->display;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getDataSources(): array
	{
		return $this->dataSources;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getTabs(): array
	{
		return $this->tabs;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'source' => $this->getSource()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'display' => $this->getDisplay()->toString(),
			'data_sources' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getDataSources(),
			),
			'tabs' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getTabs(),
			),
			'groups' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getGroups(),
			),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	#[Override]
	public function getSource(): Sources\Source
	{
		return Sources\Module::UI;
	}

}
