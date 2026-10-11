<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Subscribers;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Publishers;
use Override;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * RabbitMQ async client channel subscriber
 */
readonly class Channel implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private Publishers\Publisher $publisher,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	#[Override]
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
				'source' => Sources\Plugin::RABBITMQ->value,
				'type' => 'subscriber',
			],
		);
	}

}
