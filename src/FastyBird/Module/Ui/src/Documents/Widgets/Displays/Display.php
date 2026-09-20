<?php declare(strict_types = 1);

/**
 * Display.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use DateTimeInterface;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Documents\Exchange as ExchangeDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Widget display document
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Widgets\Displays\Display::class)]
#[ApplicationDocuments\Mapping\InheritanceType('SINGLE_TABLE')]
#[ApplicationDocuments\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[ApplicationDocuments\Mapping\MappedSuperclass]
#[ExchangeDocuments\Mapping\RoutingMap([
	Ui\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_REPORTED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_UPDATED_ROUTING_KEY,
	Ui\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Display implements Documents\Document, ApplicationDocuments\Owner, ApplicationDocuments\CreatedAt, ApplicationDocuments\UpdatedAt
{

	use ApplicationDocuments\TOwner;
	use ApplicationDocuments\TCreatedAt;
	use ApplicationDocuments\TUpdatedAt;

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $widget,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\UuidValue(),
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

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getWidget(): Uuid\UuidInterface
	{
		return $this->widget;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'source' => $this->getSource()->value,
			'widget' => $this->getWidget()->toString(),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::UI;
	}

}
