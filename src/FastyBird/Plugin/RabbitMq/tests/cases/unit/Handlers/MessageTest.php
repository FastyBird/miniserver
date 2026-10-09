<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Handlers;

use Bunny;
use DateTimeImmutable;
use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Handlers;
use FastyBird\Plugin\RabbitMq\Publishers;
use FastyBird\Plugin\RabbitMq\Tests;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Psr\Clock\ClockInterface;
use TypeError;
use ValueError;
use function assert;

/**
 * The handler does not consume a message that its own process published, recognised by the
 * sender identifier the publisher stamps on it: it answers MESSAGE_NACK, which the channel turns
 * into a nack. Every other message is handed to the exchange consumers and acknowledged. The
 * identifier is one service per process, shared by the publisher and the handler, which is why
 * each test builds the publisher and the handler around one generator.
 */
final class MessageTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ROUTING_KEY = 'fb.exchange.module.document.testing.routing.key';

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testAMessageItsOwnProcessPublishedIsNotConsumed(): void
	{
		$identifier = new Utilities\IdentifierGenerator();

		$consumer = $this->createRecordingConsumer();

		$result = $this->createHandler($identifier, $consumer)->handle($this->publish($identifier));

		self::assertSame(Handlers\Message::MESSAGE_NACK, $result);
		self::assertSame([], $consumer->calls);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testAMessageAnotherProcessPublishedIsConsumed(): void
	{
		$consumer = $this->createRecordingConsumer();

		$result = $this->createHandler(new Utilities\IdentifierGenerator(), $consumer)->handle(
			$this->publish(new Utilities\IdentifierGenerator()),
		);

		self::assertSame(Handlers\Message::MESSAGE_ACK, $result);
		self::assertSame(
			[[Sources\Module::DEVICES, self::ROUTING_KEY, ['attribute' => 'someAttribute', 'value' => 10]]],
			$consumer->calls,
		);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function testAMessageWithoutASenderIsConsumed(): void
	{
		$consumer = $this->createRecordingConsumer();

		$result = $this->createHandler(new Utilities\IdentifierGenerator(), $consumer)->handle(
			new Bunny\Message(
				null,
				1,
				false,
				'exchange_name',
				self::ROUTING_KEY,
				['source' => Sources\Module::DEVICES->value],
				'{"attribute":"someAttribute","value":10}',
			),
		);

		self::assertSame(Handlers\Message::MESSAGE_ACK, $result);
		self::assertCount(1, $consumer->calls);
	}

	/**
	 * What the RabbitMq publisher sends to the exchange for one document, as the message the
	 * broker delivers
	 */
	private function publish(Utilities\IdentifierGenerator $identifier): Bunny\Message
	{
		$sent = null;

		$channel = $this->createMock(Channels\Channel::class);
		$channel
			->expects(self::once())
			->method('publish')
			->willReturnCallback(
				static function (string $body, array $headers, string $exchange, string $routingKey) use (&$sent): bool {
					/** @var array<string, mixed> $headers */
					$sent = new Bunny\Message(null, 1, false, $exchange, $routingKey, $headers, $body);

					return true;
				},
			);

		$clock = $this->createMock(ClockInterface::class);
		$clock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		(new Publishers\Publisher('exchange_name', $channel, $identifier, $clock))->publish(
			Sources\Module::DEVICES,
			self::ROUTING_KEY,
			new Tests\Fixtures\Dummy\DummyDocument('someAttribute', 10),
		);

		assert($sent instanceof Bunny\Message);

		return $sent;
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function createHandler(
		Utilities\IdentifierGenerator $identifier,
		Consumers\Consumer $consumer,
	): Handlers\Message
	{
		// The container's own routing document factory, its mapping chain extended with the fixture
		// namespace, whose DummyDocument is routed for ROUTING_KEY
		$this->container->getByType(Documents\Mapping\Driver\MappingDriverChain::class)->addDriver(
			new Documents\Mapping\Driver\AttributeDriver([__DIR__ . '/../../../fixtures/dummy']),
			'FastyBird\\Plugin\\RabbitMq\\Tests\\Fixtures\\Dummy',
		);

		$documentFactory = $this->container->getByType(Documents\RoutingDocumentFactory::class);

		$consumers = new Consumers\Container();
		$consumers->register($consumer, null);

		return new Handlers\Message($identifier, $documentFactory, $consumers);
	}

	/**
	 * @return Consumers\Consumer&object{calls: list<array{0: Sources\Source, 1: string, 2: array<string, mixed>|null}>}
	 */
	private function createRecordingConsumer(): object
	{
		return new class implements Consumers\Consumer {

			/** @var list<array{0: Sources\Source, 1: string, 2: array<string, mixed>|null}> */
			public array $calls = [];

			public function consume(
				Sources\Source $source,
				string $routingKey,
				Documents\Document|null $document,
			): void
			{
				$this->calls[] = [$source, $routingKey, $document?->toArray()];
			}

		};
	}

}
