<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\RabbitMq\Subscribers;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Publishers;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * RabbitMQ async client channel subscriber
 *
 * @package         FastyBird:RabbitMqPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Channel implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private Publishers\Publisher $publisher,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ChannelCreated::class => 'channelCreated',
		];
	}

	public function channelCreated(Events\ChannelCreated $event): void
	{
		$this->publisher->setChannel($event->getChannel());

		$this->logger->debug(
			'Rabbit MQ channel from async client was assigned to publisher service',
			[
				'source' => MetadataTypes\Sources\Plugin::RABBITMQ->value,
				'type' => 'subscriber',
			],
		);
	}

}
