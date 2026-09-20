<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.01.24
 */

namespace FastyBird\Module\Devices\Documents\States\Channels\Properties;

use DateTimeInterface;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Documents\Exchange as ExchangeDocuments;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use Ramsey\Uuid;
use function array_merge;

/**
 * Channel property state document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document]
#[ExchangeDocuments\Mapping\RoutingMap([
	Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY,
])]
final class Property extends Devices\Documents\States\Property
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		Documents\States\StateValues $read,
		Documents\States\StateValues $get,
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
