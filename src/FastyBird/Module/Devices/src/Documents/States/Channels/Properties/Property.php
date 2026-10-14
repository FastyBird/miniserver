<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States\Channels\Properties;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property state document
 */
#[CoreDocuments\Mapping\Document]
#[CoreDocuments\Mapping\RoutingMap([
	Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED->value,
	Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED->value,
	Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED->value,
	Devices\Types\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED->value,
])]
final class Property extends Devices\Documents\States\Property
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		DevicesDocuments\States\StateValues $read,
		DevicesDocuments\States\StateValues $get,
		bool|DateTimeInterface $pending = false,
		bool $valid = false,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $read, $get, $pending, $valid, $createdAt, $updatedAt);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

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
