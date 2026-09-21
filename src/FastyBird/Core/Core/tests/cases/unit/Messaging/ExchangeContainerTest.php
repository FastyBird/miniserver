<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Messaging;

use ArrayObject;
use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Messaging\Exchange\Consumers;
use FastyBird\Core\Messaging\Exchange\Publisher;
use FastyBird\Core\Types\Metadata;
use PHPUnit\Framework\TestCase;
use function count;

final class ExchangeContainerTest extends TestCase
{

	public function testPublishReachesEveryRegisteredPublisherInRegistrationOrder(): void
	{
		/** @var ArrayObject<int, string> $log */
		$log = new ArrayObject();

		$publisherA = $this->createRecordingPublisher($log, 'a');
		$publisherB = $this->createRecordingPublisher($log, 'b');

		$container = new Publisher\Container();
		$container->register($publisherA);
		$container->register($publisherB);

		$container->publish(Metadata\Sources\Module::NOT_SPECIFIED, 'test.routing.key', null);

		self::assertSame(['a', 'b'], $log->getArrayCopy());
		self::assertCount(1, $publisherA->calls);
		self::assertCount(1, $publisherB->calls);
		self::assertSame(Metadata\Sources\Module::NOT_SPECIFIED, $publisherA->calls[0][0]);
		self::assertSame('test.routing.key', $publisherA->calls[0][1]);
		self::assertNull($publisherA->calls[0][2]);
	}

	public function testPublishWithNoRegisteredPublisherDoesNotThrow(): void
	{
		$container = new Publisher\Container();

		$result = $container->publish(Metadata\Sources\Module::NOT_SPECIFIED, 'test.routing.key', null);

		self::assertTrue($result);
	}

	public function testResetClearsPublisherRegistrations(): void
	{
		/** @var ArrayObject<int, string> $log */
		$log = new ArrayObject();

		$publisher = $this->createRecordingPublisher($log, 'a');

		$container = new Publisher\Container();
		$container->register($publisher);
		$container->reset();

		$container->publish(Metadata\Sources\Module::NOT_SPECIFIED, 'test.routing.key', null);

		self::assertCount(0, $publisher->calls);
	}

	public function testRegisteringTheSamePublisherInstanceTwiceCallsItOnlyOnce(): void
	{
		/** @var ArrayObject<int, string> $log */
		$log = new ArrayObject();

		$publisher = $this->createRecordingPublisher($log, 'a');

		$container = new Publisher\Container();
		$container->register($publisher);
		$container->register($publisher);

		$container->publish(Metadata\Sources\Module::NOT_SPECIFIED, 'test.routing.key', null);

		// SplObjectStorage keys registrations by object identity, and register() guards with
		// offsetExists(), so registering the same instance twice is a no-op the second time.
		self::assertCount(1, $publisher->calls);
	}

	public function testConsumeReachesEveryRegisteredConsumer(): void
	{
		/** @var ArrayObject<int, string> $log */
		$log = new ArrayObject();

		$consumerA = $this->createRecordingConsumer($log, 'a');
		$consumerB = $this->createRecordingConsumer($log, 'b');

		$container = new Consumers\Container();
		$container->register($consumerA, null);
		$container->register($consumerB, null);

		$container->consume(Metadata\Sources\Module::NOT_SPECIFIED, 'test.routing.key', null);

		self::assertSame(['a', 'b'], $log->getArrayCopy());
		self::assertCount(1, $consumerA->calls);
		self::assertCount(1, $consumerB->calls);
		self::assertSame(Metadata\Sources\Module::NOT_SPECIFIED, $consumerA->calls[0][0]);
		self::assertSame('test.routing.key', $consumerA->calls[0][1]);
		self::assertNull($consumerA->calls[0][2]);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testDisableStopsConsumingAndEnableRestoresIt(): void
	{
		/** @var ArrayObject<int, string> $log */
		$log = new ArrayObject();

		$consumer = $this->createRecordingConsumer($log, 'a');

		$container = new Consumers\Container();
		$container->register($consumer, null);

		$container->consume(Metadata\Sources\Module::NOT_SPECIFIED, 'first.key', null);
		$afterFirstConsume = count($consumer->calls);
		self::assertSame(1, $afterFirstConsume);

		$container->disable($consumer::class);
		$container->consume(Metadata\Sources\Module::NOT_SPECIFIED, 'second.key', null);
		$afterDisabledConsume = count($consumer->calls);
		self::assertSame($afterFirstConsume, $afterDisabledConsume);

		$container->enable($consumer::class);
		$container->consume(Metadata\Sources\Module::NOT_SPECIFIED, 'third.key', null);
		$afterEnabledConsume = count($consumer->calls);
		self::assertSame($afterFirstConsume + 1, $afterEnabledConsume);
	}

	public function testInfoReturnsWhatTheConstructorWasGiven(): void
	{
		$enabledWithKey = new Consumers\Info('some.routing.key', true);

		self::assertSame('some.routing.key', $enabledWithKey->getRoutingKey());
		self::assertTrue($enabledWithKey->isEnabled());

		$disabledWithoutKey = new Consumers\Info(null, false);

		self::assertNull($disabledWithoutKey->getRoutingKey());
		self::assertFalse($disabledWithoutKey->isEnabled());
	}

	/**
	 * @param ArrayObject<int, string> $log
	 *
	 * @return Publisher\Publisher&object{calls: list<array{0: Metadata\Sources\Source, 1: string, 2: Documents\Document|null}>}
	 */
	private function createRecordingPublisher(ArrayObject $log, string $label): object
	{
		return new class ($log, $label) implements Publisher\Publisher {

			/** @var list<array{0: Metadata\Sources\Source, 1: string, 2: Documents\Document|null}> */
			public array $calls = [];

			/**
			 * @param ArrayObject<int, string> $log
			 */
			public function __construct(
				private readonly ArrayObject $log,
				private readonly string $label,
			)
			{
			}

			public function publish(
				Metadata\Sources\Source $source,
				string $routingKey,
				Documents\Document|null $entity,
			): bool
			{
				$this->calls[] = [$source, $routingKey, $entity];
				$this->log[] = $this->label;

				return true;
			}

		};
	}

	/**
	 * @param ArrayObject<int, string> $log
	 *
	 * @return Consumers\Consumer&object{calls: list<array{0: Metadata\Sources\Source, 1: string, 2: Documents\Document|null}>}
	 */
	private function createRecordingConsumer(ArrayObject $log, string $label): object
	{
		return new class ($log, $label) implements Consumers\Consumer {

			/** @var list<array{0: Metadata\Sources\Source, 1: string, 2: Documents\Document|null}> */
			public array $calls = [];

			/**
			 * @param ArrayObject<int, string> $log
			 */
			public function __construct(
				private readonly ArrayObject $log,
				private readonly string $label,
			)
			{
			}

			public function consume(
				Metadata\Sources\Source $source,
				string $routingKey,
				Documents\Document|null $document,
			): void
			{
				$this->calls[] = [$source, $routingKey, $document];
				$this->log[] = $this->label;
			}

		};
	}

}
