<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Channels\Properties;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Override;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Channel property document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Properties\Property::class)]
#[CoreDocuments\Mapping\InheritanceType('SINGLE_TABLE')]
#[CoreDocuments\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[CoreDocuments\Mapping\DiscriminatorMap([
	Entities\Channels\Properties\Dynamic::TYPE => Dynamic::class,
	Entities\Channels\Properties\Mapped::TYPE => Mapped::class,
	Entities\Channels\Properties\Variable::TYPE => Variable::class,
])]
#[CoreDocuments\Mapping\MappedSuperclass]
#[CoreDocuments\Mapping\RoutingMap([
	DevicesTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED->value,
	DevicesTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_CREATED->value,
	DevicesTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_UPDATED->value,
	DevicesTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_DELETED->value,
])]
abstract class Property extends DevicesDocuments\Property
{

	/**
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		private readonly Uuid\UuidInterface $channel,
		DevicesTypes\PropertyCategory $category,
		string $identifier,
		string|null $name,
		ValuesTypes\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		bool|float|int|string|null $default = null,
		Uuid\UuidInterface|string|null $valueTransformer = null,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct(
			$id,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$default,
			$valueTransformer,
			$owner,
			$createdAt,
			$updatedAt,
		);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'channel' => $this->getChannel()->toString(),
			],
		);
	}

}
