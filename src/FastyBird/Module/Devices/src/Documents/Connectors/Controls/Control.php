<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Devices\Documents\Connectors\Controls;

use DateTimeInterface;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Connector control document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Connectors\Controls\Control::class)]
#[ExchangeDocuments\Mapping\RoutingMap([
	Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
])]
final class Control implements Documents\Document, ApplicationDocuments\Owner, ApplicationDocuments\CreatedAt, ApplicationDocuments\UpdatedAt
{

	use ApplicationDocuments\TOwner;
	use ApplicationDocuments\TCreatedAt;
	use ApplicationDocuments\TUpdatedAt;

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
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

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'connector' => $this->getConnector()->toString(),
			'name' => $this->getName(),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

}
