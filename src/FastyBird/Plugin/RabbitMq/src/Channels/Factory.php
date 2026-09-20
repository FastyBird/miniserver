<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Channel
 * @since          1.0.0
 *
 * @date           26.03.23
 */

namespace FastyBird\Plugin\RabbitMq\Channels;

use Bunny;
use FastyBird\Core\Messaging\Exchange as ExchangeExchange;
use FastyBird\Core\Constants\Metadata\Constants as MetadataConstants;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Exceptions;
use FastyBird\Plugin\RabbitMq\Handlers;
use Nette\Utils;
use Psr\EventDispatcher;
use function assert;

/**
 * RabbitMQ async client factory
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Channel
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory implements ExchangeExchange\Factory
{

	private const EXCHANGE_TYPE = 'topic';

	public function __construct(
		private readonly string $exchangeName,
		private readonly Connections\Connection $connection,
		private readonly Handlers\Message $messagesHandler,
		private readonly string|null $queueName = null,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(): void
	{
		$client = new Bunny\Client([
			'host' => $this->connection->getHost(),
			'port' => $this->connection->getPort(),
			'vhost' => $this->connection->getVhost(),
			'user' => $this->connection->getUsername(),
			'password' => $this->connection->getPassword(),
			'heartbeat' => 30,
		]);

		$channel = $client->channel();
		assert($channel instanceof Bunny\Channel);

		$channel->qos(0, 5);

		$this->dispatcher?->dispatch(new Events\ChannelCreated($channel));

		$autoDeleteQueue = false;
		$queueName = $this->queueName;

		if ($queueName === null) {
			$queueName = 'rabbit.plugin_' . Utils\Random::generate();

			$autoDeleteQueue = true;
		}

		// Create exchange
		$channel
			// Try to create exchange
			->exchangeDeclare(
				$this->exchangeName,
				self::EXCHANGE_TYPE,
				false,
				true,
			);

		// Create queue to connect to...
		$channel->queueDeclare(
			$queueName,
			false,
			true,
			false,
			$autoDeleteQueue,
		);

		$channel->queueBind(
			$queueName,
			$this->exchangeName,
			MetadataConstants::MESSAGE_BUS_PREFIX_KEY . '.#',
		);

		$channel->consume(
			function (Bunny\Message $message, Bunny\Channel $channel, Bunny\Client $client): void {
				$result = $this->messagesHandler->handle($message);

				switch ($result) {
					case Handlers\Message::MESSAGE_ACK:
						$channel->ack($message); // Acknowledge message

						break;
					case Handlers\Message::MESSAGE_NACK:
						$channel->nack($message); // Message will be re-queued

						break;
					case Handlers\Message::MESSAGE_REJECT:
						$channel->reject($message, false); // Message will be discarded

						break;
					case Handlers\Message::MESSAGE_REJECT_AND_TERMINATE:
						$channel->reject($message, false); // Message will be discarded

						$client->disconnect();

						break;
					default:
						throw new Exceptions\InvalidArgument('Unknown return value of message handler');
				}
			},
			$queueName,
		);
	}

}
