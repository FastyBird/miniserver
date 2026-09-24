<?php declare(strict_types = 1);

/**
 * Notification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Notifications;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Notification document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Documents\Mapping\Document(entity: Entities\Notifications\Notification::class)]
#[Documents\Mapping\InheritanceType('SINGLE_TABLE')]
#[Documents\Mapping\DiscriminatorColumn(name: 'type', type: 'string')]
#[Documents\Mapping\MappedSuperclass]
#[Documents\Mapping\RoutingMap([
	Triggers\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_REPORTED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_CREATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_UPDATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Notification implements Documents\Document, Documents\Owner
{

	use Documents\HasOwner;

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $trigger,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $enabled,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'type' => $this->getType(),
			'enabled' => $this->isEnabled(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
