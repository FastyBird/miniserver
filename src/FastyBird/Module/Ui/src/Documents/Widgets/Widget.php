<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
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
		private readonly Uuid\UuidInterface $id,
		private readonly Uuid\UuidInterface $display,
		#[CoreDocuments\Mapping\RefuseBlank]
		private readonly string $identifier,
		#[CoreDocuments\Mapping\MapBlankToNull]
		private readonly string|null $name = null,
		#[Configurator\MapFromKey('data_sources')]
		private readonly array $dataSources = [],
		private readonly array $tabs = [],
		private readonly array $groups = [],
		#[CoreDocuments\Mapping\MapBlankToNull]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
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
