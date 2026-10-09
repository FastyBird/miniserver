<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Channels;

use Bunny;
use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Exceptions;
use FastyBird\Plugin\RabbitMq\Handlers;
use FastyBird\Plugin\RabbitMq\Tests;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use TypeError;
use ValueError;

/**
 * What the channel tells the broker about a delivered message. A message the process published
 * itself is acknowledged: a nack would re-queue it, the process's own queue would deliver it to
 * the process again, and the two would repeat for ever (#685).
 */
final class FactoryTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ROUTING_KEY = 'fb.exchange.module.document.testing.routing.key';

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testAMessageTheProcessPublishedItselfIsAcknowledgedAndNeverRequeued(): void
	{
		$identifier = new Utilities\IdentifierGenerator();

		$message = $this->deliver($identifier->getIdentifier(), Sources\Module::DEVICES->value);

		$channel = $this->createMock(Bunny\Channel::class);
		$channel
			->expects(self::once())
			->method('ack')
			->with($message);
		$channel
			->expects(self::never())
			->method('nack');
		$channel
			->expects(self::never())
			->method('reject');

		$client = $this->createMock(Bunny\Client::class);
		$client
			->expects(self::never())
			->method('disconnect');

		$consumer = new class implements Consumers\Consumer {

			public int $calls = 0;

			public function consume(
				Sources\Source $source,
				string $routingKey,
				Documents\Document|null $document,
			): void
			{
				$this->calls++;
			}

		};

		$this->createFactory($identifier, $consumer)->answer($message, $channel, $client);

		self::assertSame(0, $consumer->calls);
	}

	/**
	 * A control for the test above: the channel does tell messages apart. Here a message from
	 * another process with a source nobody owns is discarded, not re-queued.
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testAMessageThatCannotBeUsedIsRejectedWithoutRequeue(): void
	{
		$message = $this->deliver((new Utilities\IdentifierGenerator())->getIdentifier(), 'unknown-source');

		$channel = $this->createMock(Bunny\Channel::class);
		$channel
			->expects(self::once())
			->method('reject')
			->with($message, false);
		$channel
			->expects(self::never())
			->method('ack');
		$channel
			->expects(self::never())
			->method('nack');

		$this->createFactory(
			new Utilities\IdentifierGenerator(),
			new class implements Consumers\Consumer {

				public function consume(
					Sources\Source $source,
					string $routingKey,
					Documents\Document|null $document,
				): void
				{
					// The message is rejected before any consumer is reached
				}

			},
		)->answer($message, $channel, $this->createMock(Bunny\Client::class));
	}

	private function deliver(string $senderId, string $source): Bunny\Message
	{
		return new Bunny\Message(
			null,
			1,
			false,
			'exchange_name',
			self::ROUTING_KEY,
			['sender_id' => $senderId, 'source' => $source],
			'{"attribute":"someAttribute","value":10}',
		);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function createFactory(
		Utilities\IdentifierGenerator $identifier,
		Consumers\Consumer $consumer,
	): Channels\Factory
	{
		$consumers = new Consumers\Container();
		$consumers->register($consumer, null);

		return new Channels\Factory(
			'exchange_name',
			new Connections\Connection(),
			new Handlers\Message(
				$identifier,
				$this->container->getByType(Documents\RoutingDocumentFactory::class),
				$consumers,
			),
		);
	}

}
