<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use BinSoul\Net\Mqtt;
use Closure;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Tests\Support;
use PHPUnit\Framework\TestCase;
use React\EventLoop;
use React\Promise;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use function array_map;
use function func_get_args;

final class ClientTest extends TestCase
{

	private const string CLIENT_ID = 'test-client';

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\Client::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	public function testConnectNotifiesListenersInOrderWithTheConnection(): void
	{
		$events = [];

		$this->runScenario(function (API\Client $client) use (&$events): Promise\PromiseInterface {
			$client->onConnect($this->record('first', $events));
			$client->onConnect($this->record('second', $events));

			return $client->connect(1)->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
		});

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Mqtt\Connection::class, $event[1][0]);
			self::assertSame(self::CLIENT_ID, $event[1][0]->getClientID());
		}
	}

	public function testDisconnectNotifiesDisconnectThenClose(): void
	{
		$events = [];

		$this->runScenario(function (API\Client $client) use (&$events): Promise\PromiseInterface {
			$client->onDisconnect($this->record('disconnect', $events));
			$client->onClose($this->record('close', $events));

			return $client->connect(1)->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
		});

		self::assertSame([['disconnect', []], ['close', []]], $events);
	}

	public function testSubscribeAndUnsubscribeNotifyListenersWithTheSubscription(): void
	{
		$events = [];
		$subscription = new Mqtt\DefaultSubscription('fb/test/#');

		$this->runScenario(function (API\Client $client) use (&$events, $subscription): Promise\PromiseInterface {
			$client->onSubscribe($this->record('subscribe', $events));
			$client->onUnsubscribe($this->record('unsubscribe', $events));

			return $client->connect(1)
				->then(static fn (): Promise\PromiseInterface => $client->subscribe($subscription))
				->then(static fn (): Promise\PromiseInterface => $client->unsubscribe($subscription))
				->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
		});

		self::assertCount(2, $events);
		self::assertSame('subscribe', $events[0][0]);
		self::assertSame([$subscription], $events[0][1]);
		self::assertSame('unsubscribe', $events[1][0]);
		self::assertSame([[$subscription]], $events[1][1]);
	}

	public function testPublishNotifiesListenersWithTheMessage(): void
	{
		$events = [];

		$broker = $this->runScenario(function (API\Client $client) use (&$events): Promise\PromiseInterface {
			$client->onPublish($this->record('publish', $events));

			return $client->connect(1)
				->then(static fn (): Promise\PromiseInterface => $client->publish('fb/test/topic', 'payload'))
				->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
		});

		self::assertCount(1, $events);
		self::assertSame('publish', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Mqtt\Message::class, $events[0][1][0]);
		self::assertSame('fb/test/topic', $events[0][1][0]->getTopic());
		self::assertSame('payload', $events[0][1][0]->getPayload());
		self::assertSame([['fb/test/topic', 'payload']], $broker->getPublished());
	}

	public function testReceivedMessageNotifiesListenersInOrderWithTheMessage(): void
	{
		$events = [];

		$this->runScenario(
			function (API\Client $client, Support\FakeBroker $broker) use (&$events): Promise\PromiseInterface {
				$received = new Promise\Deferred();

				$client->onMessage($this->record('first', $events));
				$client->onMessage($this->record('second', $events));
				$client->onMessage(static function () use ($received): void {
					$received->resolve(null);
				});

				return $client->connect(1)
					->then(static function () use ($broker, $received): Promise\PromiseInterface {
						$broker->pushMessage('fb/test/topic', 'hello');

						return $received->promise();
					})
					->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
			},
		);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Mqtt\Message::class, $event[1][0]);
			self::assertSame('fb/test/topic', $event[1][0]->getTopic());
			self::assertSame('hello', $event[1][0]->getPayload());
		}
	}

	public function testWarningNotifiesListenersWithTheError(): void
	{
		$events = [];

		$this->runScenario(
			function (API\Client $client, Support\FakeBroker $broker) use (&$events): Promise\PromiseInterface {
				$warned = new Promise\Deferred();

				$client->onWarning($this->record('warning', $events));
				$client->onWarning(static function () use ($warned): void {
					$warned->resolve(null);
				});

				return $client->connect(1)
					->then(static function () use ($broker, $warned): Promise\PromiseInterface {
						$broker->pushUnexpectedPingResponse();

						return $warned->promise();
					})
					->then(static fn (): Promise\PromiseInterface => $client->disconnect(1));
			},
		);

		self::assertCount(1, $events);
		self::assertSame('warning', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Throwable::class, $events[0][1][0]);
		self::assertStringContainsString('unexpected packet', $events[0][1][0]->getMessage());
	}

	public function testErrorNotifiesListenersWithTheError(): void
	{
		$events = [];

		$this->runScenario(
			function (API\Client $client, Support\FakeBroker $broker) use (&$events): Promise\PromiseInterface {
				$client->onError($this->record('error', $events));

				// Nobody listens on the port any more, so the connection is refused
				$broker->close();

				return $client->connect(1)->then(
					static function (): void {
						throw new RuntimeException('The connection should have been refused');
					},
					static fn (): null => null,
				);
			},
		);

		self::assertCount(1, $events);
		self::assertSame('error', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Throwable::class, $events[0][1][0]);
	}

	/**
	 * @param Closure(API\Client, Support\FakeBroker): Promise\PromiseInterface<mixed> $scenario
	 */
	private function runScenario(Closure $scenario): Support\FakeBroker
	{
		$eventLoop = new EventLoop\StreamSelectLoop();

		$broker = new Support\FakeBroker($eventLoop);

		$client = new API\Client(
			self::CLIENT_ID,
			'127.0.0.1',
			$broker->getPort(),
			new FbMqtt\Logger(),
			$eventLoop,
		);

		$finished = false;
		$failure = null;

		$guard = $eventLoop->addTimer(10, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$scenario($client, $broker)
			->then(
				static function () use (&$finished): void {
					$finished = true;
				},
				static function (Throwable $ex) use (&$failure): void {
					$failure = $ex;
				},
			)
			->finally(static function () use ($eventLoop, $guard, $broker): void {
				$eventLoop->cancelTimer($guard);
				$broker->close();
				$eventLoop->stop();
			});

		$eventLoop->run();

		if ($failure !== null) {
			throw $failure;
		}

		self::assertTrue($finished, 'The scenario did not finish before the guard timer fired');

		return $broker;
	}

	/**
	 * @param array<int, array{string, array<mixed>}> $events
	 *
	 * @return Closure(): void
	 */
	private function record(string $name, array &$events): Closure
	{
		return static function () use ($name, &$events): void {
			$events[] = [$name, func_get_args()];
		};
	}

}
