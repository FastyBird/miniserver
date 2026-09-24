<?php declare(strict_types = 1);

/**
 * MessageConsumed.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.02.24
 */

namespace FastyBird\Plugin\RabbitMq\Events;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use Symfony\Contracts\EventDispatcher;

/**
 * Message was consumed by consumer
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MessageConsumed extends EventDispatcher\Event
{

	public function __construct(
		private readonly Sources\Source $source,
		private readonly string $routingKey,
		private readonly Documents\Document|null $entity,
	)
	{
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

	public function getRoutingKey(): string
	{
		return $this->routingKey;
	}

	public function getEntity(): Documents\Document|null
	{
		return $this->entity;
	}

}
