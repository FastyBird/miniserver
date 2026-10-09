<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Exchange;

use DateTimeImmutable;
use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exchange;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Tests;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Nette\Utils;
use Psr\Clock\ClockInterface;
use TypeError;
use ValueError;
use function assert;
use function is_string;

/**
 * The handler drops a message that its own process published, recognised by the sender
 * identifier the publisher stamps on it, and hands every other message to the exchange consumers.
 * The identifier is one service per process, shared by the publishers and the handler, which is
 * why each test builds the publisher and the handler around one generator.
 */
final class HandlerTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ROUTING_KEY = 'fb.exchange.module.document.testing.routing.key';

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws Utils\JsonException
	 * @throws ValueError
	 */
	public function testAMessageItsOwnProcessPublishedIsNotConsumed(): void
	{
		$identifier = new Utilities\IdentifierGenerator();

		$consumer = $this->createRecordingConsumer();

		$this->createHandler($identifier, $consumer)->handle($this->publish($identifier));

		self::assertSame([], $consumer->calls);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws Utils\JsonException
	 * @throws ValueError
	 */
	public function testAMessageAnotherProcessPublishedIsConsumed(): void
	{
		$consumer = $this->createRecordingConsumer();

		$this->createHandler(new Utilities\IdentifierGenerator(), $consumer)->handle(
			$this->publish(new Utilities\IdentifierGenerator()),
		);

		self::assertSame(
			[[Sources\Module::DEVICES, self::ROUTING_KEY, ['attribute' => 'someAttribute', 'value' => 10]]],
			$consumer->calls,
		);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws TypeError
	 * @throws Utils\JsonException
	 * @throws ValueError
	 */
	public function testAMessageWithoutASenderIsConsumed(): void
	{
		$consumer = $this->createRecordingConsumer();

		$this->createHandler(new Utilities\IdentifierGenerator(), $consumer)->handle(Utils\Json::encode([
			'source' => Sources\Module::DEVICES->value,
			'routing_key' => self::ROUTING_KEY,
			'data' => ['attribute' => 'someAttribute', 'value' => 10],
		]));

		self::assertCount(1, $consumer->calls);
	}

	/**
	 * What the RedisDb publisher sends to the exchange channel for one document
	 */
	private function publish(Utilities\IdentifierGenerator $identifier): string
	{
		$sent = null;

		$client = $this->createMock(Clients\Client::class);
		$client
			->expects(self::once())
			->method('publish')
			->willReturnCallback(static function (string $channel, string $payload) use (&$sent): bool {
				$sent = $payload;

				return true;
			});

		$clock = $this->createMock(ClockInterface::class);
		$clock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		(new Publishers\Publisher($identifier, 'exchange_channel', $client, $clock))->publish(
			Sources\Module::DEVICES,
			self::ROUTING_KEY,
			new Tests\Fixtures\Dummy\DummyDocument('someAttribute', 10),
		);

		assert(is_string($sent));

		return $sent;
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function createHandler(
		Utilities\IdentifierGenerator $identifier,
		Consumers\Consumer $consumer,
	): Exchange\Handler
	{
		// The container's own routing document factory, its mapping chain extended with the fixture
		// namespace, whose DummyDocument is routed for ROUTING_KEY
		$this->container->getByType(Documents\Mapping\Driver\MappingDriverChain::class)->addDriver(
			new Documents\Mapping\Driver\AttributeDriver([__DIR__ . '/../../../fixtures/dummy']),
			'FastyBird\\Plugin\\RedisDb\\Tests\\Fixtures\\Dummy',
		);

		$documentFactory = $this->container->getByType(Documents\RoutingDocumentFactory::class);

		$consumers = new Consumers\Container();
		$consumers->register($consumer, null);

		return new Exchange\Handler($identifier, $documentFactory, $consumers);
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
