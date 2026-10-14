<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use DateTimeImmutable;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\Tests\Support;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Subscribers;
use FastyBird\Core\WebSockets\Topics;
use Nette\Utils;
use Override;
use React\Socket;
use Throwable;
use function is_string;

/**
 * An event published to a WAMP topic reaches a subscriber only while the subscriber's token still
 * resolves to an identity: the check a message from the client gets, repeated before an event is
 * pushed to it, at most once per Subscribers\Client::RECHECK_INTERVAL seconds. A subscriber that
 * fails it gets nothing, and is closed with a 401 exactly as a message from it would be.
 *
 * The container's WAMP application and client subscriber are the ones under test
 * (eventRecipients.neon). The identity provider stands in for the accounts module's lookup of the
 * persisted token, the clock is one the test moves, and the topic's controller does nothing.
 */
final class EventRecipientsTest extends BaseTestCase
{

	private const string TOPIC = '/probe/v1/exchange';

	private const string USER = '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a';

	/** @var array<int, list<string>> */
	private array $sent = [];

	/** @var array<int, int> */
	private array $closed = [];

	private Support\MovableClock $clock;

	private Support\TokenIdentities $identities;

	/**
	 * @throws Throwable
	 */
	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer(__DIR__ . '/eventRecipients.neon');

		// The topic's controller does nothing
		$this->container->getByType(Controllers\ControllerFactory::class)
			->setMapping(['Probe' => 'FastyBird\\Core\\Tests\\Fixtures\\Dummy\\Dummy*Controller']);

		$this->clock = $this->container->getByType(Support\MovableClock::class);
		$this->identities = $this->container->getByType(Support\TokenIdentities::class);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnAuthenticatedSubscriberReceivesTheEvent(): void
	{
		$client = $this->connect(1, $this->issue());
		$this->subscribe($client);

		$this->publish('first');

		self::assertSame(['first'], $this->eventsOf($client));
		self::assertArrayNotHasKey(1, $this->closed);
	}

	/**
	 * @throws Throwable
	 */
	public function testASubscriberThatWasNeverAuthenticatedReceivesNothing(): void
	{
		$client = $this->client(1, $this->issue());
		$this->subscribe($client);

		$this->publish('first');

		self::assertSame([], $this->eventsOf($client));
	}

	/**
	 * @throws Throwable
	 */
	public function testASubscriberWhoseTokenWasRevokedReceivesNothingOnceItIsCheckedAgain(): void
	{
		$token = $this->issue();

		$client = $this->connect(1, $token);
		$this->subscribe($client);

		$this->publish('first');

		unset($this->identities->resolvable[$token]);

		$this->advance(Subscribers\Client::RECHECK_INTERVAL);

		$this->publish('second');
		$this->publish('third');

		self::assertSame(['first'], $this->eventsOf($client));
		self::assertNull($client->getIdentity());
		self::assertArrayHasKey(1, $this->closed);
		self::assertTrue($this->wasRefused($client));
	}

	/**
	 * @throws Throwable
	 */
	public function testASubscriberWhoseTokenExpiredReceivesNothing(): void
	{
		$client = $this->connect(1, $this->issue(new DateTimeImmutable('2020-04-01T12:00:30+00:00')));
		$this->subscribe($client);

		$this->publish('first');

		$this->advance(60);

		$this->publish('second');

		self::assertSame(['first'], $this->eventsOf($client));
		self::assertArrayHasKey(1, $this->closed);
	}

	/**
	 * The cost of the check: one lookup when the client connects, none for the events published
	 * within the interval after it, and one for the first event after the interval
	 *
	 * @throws Throwable
	 */
	public function testTheTokenIsLookedUpAgainAtMostOncePerInterval(): void
	{
		$client = $this->connect(1, $this->issue());
		$this->subscribe($client);

		self::assertSame(1, $this->identities->lookups);

		$this->publish('first');
		$this->advance(Subscribers\Client::RECHECK_INTERVAL - 1);
		$this->publish('second');

		self::assertSame(1, $this->identities->lookups);

		$this->advance(1);
		$this->publish('third');
		$this->publish('fourth');

		self::assertSame(2, $this->identities->lookups);
		self::assertSame(['first', 'second', 'third', 'fourth'], $this->eventsOf($client));
	}

	/**
	 * The refused subscriber is closed, and so taken out of the topic, while the event is being
	 * sent to the topic's subscribers
	 *
	 * @throws Throwable
	 */
	public function testTheOtherSubscribersStillReceiveTheEvent(): void
	{
		$refused = $this->issue();

		$first = $this->connect(1, $refused);
		$second = $this->connect(2, $this->issue());
		$third = $this->connect(3, $this->issue());

		$this->subscribe($first);
		$this->subscribe($second);
		$this->subscribe($third);

		unset($this->identities->resolvable[$refused]);

		$this->advance(Subscribers\Client::RECHECK_INTERVAL);

		$this->publish('first');

		self::assertSame([], $this->eventsOf($first));
		self::assertSame(['first'], $this->eventsOf($second));
		self::assertSame(['first'], $this->eventsOf($third));
		self::assertCount(2, $this->container->getByType(Topics\Storage::class)->getTopic(self::TOPIC));
	}

	/**
	 * A lookup that cannot be made sends nothing, and leaves the client connected: the first event
	 * after the interval asks again
	 *
	 * @throws Throwable
	 */
	public function testAFailedLookupSendsNothingAndKeepsTheClient(): void
	{
		$client = $this->connect(1, $this->issue());
		$this->subscribe($client);

		$this->advance(Subscribers\Client::RECHECK_INTERVAL);

		$this->identities->failing = true;

		$this->publish('first');

		self::assertSame([], $this->eventsOf($client));
		self::assertArrayNotHasKey(1, $this->closed);

		$this->identities->failing = false;

		$this->advance(Subscribers\Client::RECHECK_INTERVAL);

		$this->publish('second');

		self::assertSame(['second'], $this->eventsOf($client));
	}

	/**
	 * A failed lookup is not tried again for the interval, so a database that cannot be reached
	 * costs one lookup per subscriber per interval, not one per event; the events in between are
	 * not sent
	 *
	 * @throws Throwable
	 */
	public function testAFailedLookupIsTriedAgainAtMostOncePerInterval(): void
	{
		$client = $this->connect(1, $this->issue());
		$this->subscribe($client);

		$this->advance(Subscribers\Client::RECHECK_INTERVAL);

		$this->identities->failing = true;
		$this->identities->lookups = 0;

		$this->publish('first');
		$this->advance(Subscribers\Client::RECHECK_INTERVAL - 1);
		$this->publish('second');

		self::assertSame(1, $this->identities->lookups);
		self::assertSame([], $this->eventsOf($client));

		// Once the database answers again, the events before the next lookup are not sent either
		$this->identities->failing = false;

		$this->publish('third');

		self::assertSame(1, $this->identities->lookups);
		self::assertSame([], $this->eventsOf($client));

		$this->advance(1);
		$this->publish('fourth');

		self::assertSame(2, $this->identities->lookups);
		self::assertSame(['fourth'], $this->eventsOf($client));
		self::assertArrayNotHasKey(1, $this->closed);
	}

	/**
	 * @throws Throwable
	 */
	private function issue(DateTimeImmutable|null $expiration = null): string
	{
		$token = $this->container->getByType(Identity\TokenBuilder::class)
			->build(self::USER, ['user'], $expiration ?? new DateTimeImmutable('2020-04-01T18:00:00+00:00'))
			->toString();

		$this->identities->resolvable[$token] = true;

		return $token;
	}

	private function advance(int $seconds): void
	{
		$this->clock->advance($seconds);
	}

	/**
	 * A client the server has accepted: its connect has been checked by the client subscriber
	 *
	 * @throws Throwable
	 */
	private function connect(int $id, string $token): Entities\Client
	{
		$client = $this->client($id, $token);

		$this->container->getByType(Subscribers\Client::class)
			->clientConnected(new Events\ClientConnected($client, $client->getRequest()));

		self::assertNotNull($client->getIdentity());

		return $client;
	}

	/**
	 * @throws Throwable
	 */
	private function client(int $id, string $token): Entities\Client
	{
		$request = (new Handshake\RequestFactory())->createHttpRequest(
			"GET / HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n"
			. 'Authorization: Bearer ' . $token . "\r\n\r\n",
		);

		self::assertNotNull($request);

		$this->sent[$id] = [];

		$protocol = $this->getMockBuilder(Encoding\RFC6455::class)
			->onlyMethods(['send', 'close'])
			->getMock();
		$protocol->method('send')->willReturnCallback(function (Entities\Client $client, mixed $payload): void {
			self::assertTrue(is_string($payload));

			$this->sent[$client->getId()][] = $payload;
		});
		// Closing a connection ends in the WAMP application's handleClose(), as the server's wrapper
		// calls it, which takes the client out of every topic
		$protocol->method('close')->willReturnCallback(function (Entities\Client $client, int|null $code = null): void {
			$this->closed[$client->getId()] = $code ?? 0;

			$this->container->getByType(Controllers\WampApplication::class)->handleClose(
				$client,
				$client->getRequest(),
			);
		});

		$client = new Entities\WampClient($id, $this->createMock(Socket\ConnectionInterface::class));
		$client->setRequest($request);
		$client->httpHeadersReceived = true;
		$client->setWebSocket(new Entities\WebSocket(true, false, $protocol));

		return $client;
	}

	/**
	 * @throws Throwable
	 */
	private function subscribe(Entities\Client $client): void
	{
		$this->container->getByType(Controllers\WampApplication::class)->handleMessage(
			$client,
			$client->getRequest(),
			Utils\Json::encode([Controllers\WampApplication::MSG_SUBSCRIBE, self::TOPIC]),
		);

		self::assertTrue($this->container->getByType(Topics\Storage::class)->getTopic(self::TOPIC)->has($client));
		self::assertArrayNotHasKey($client->getId(), $this->closed);
	}

	/**
	 * Publishes as the modules' sockets bridges do: the topic is fetched from the topics storage
	 *
	 * @throws Throwable
	 */
	private function publish(string $event): void
	{
		$this->container->getByType(Topics\Storage::class)->getTopic(self::TOPIC)->broadcast($event);
	}

	/**
	 * @return list<string>
	 *
	 * @throws Throwable
	 */
	private function eventsOf(Entities\Client $client): array
	{
		$events = [];

		foreach ($this->sent[$client->getId()] ?? [] as $payload) {
			$frame = Utils\Json::decode($payload, forceArrays: true);
			self::assertIsArray($frame);

			if (($frame[0] ?? null) === Controllers\WampApplication::MSG_EVENT) {
				self::assertIsString($frame[2] ?? null);

				$events[] = $frame[2];
			}
		}

		return $events;
	}

	/**
	 * Whether the client was sent the 401 a refused client gets before it is closed
	 *
	 * @throws Throwable
	 */
	private function wasRefused(Entities\Client $client): bool
	{
		foreach ($this->sent[$client->getId()] ?? [] as $payload) {
			$frame = Utils\Json::decode($payload, forceArrays: true);
			self::assertIsArray($frame);

			if (($frame[0] ?? null) === 'HTTP/1.1 401') {
				return true;
			}
		}

		return false;
	}

}
