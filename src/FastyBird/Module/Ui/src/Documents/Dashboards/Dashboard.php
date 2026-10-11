<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Dashboards;

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
 * Dashboard document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Dashboards\Dashboard::class)]
#[CoreDocuments\Mapping\RoutingMap([
	Ui\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_REPORTED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_UPDATED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_DELETED_ROUTING_KEY,
])]
final readonly class Dashboard implements UiDocuments\Document, CoreDocuments\Owner, CoreDocuments\CreatedAt, CoreDocuments\UpdatedAt
{

	use CoreDocuments\HasOwner;
	use CoreDocuments\HasCreatedAt;
	use CoreDocuments\HasUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $tabs
	 */
	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $comment = null,
		#[ObjectMapper\Rules\IntValue()]
		private int $priority = 0,
		#[ObjectMapper\Rules\ArrayOf(
			new Rules\UuidValue(),
		)]
		private array $tabs = [],
		#[ObjectMapper\Rules\AnyOf([
			new Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		protected DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getTabs(): array
	{
		return $this->tabs;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'priority' => $this->getPriority(),
			'tabs' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getTabs(),
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
