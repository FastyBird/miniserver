<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use FastyBird\Core\Security\Types as SecurityTypes;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Subscribers;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types as AccountsTypes;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Socket;
use Throwable;
use function is_string;

/**
 * A WebSocket client subscribed to a topic receives its events only while its token could still
 * sign in: the token is active, and so are its identity and its account. The WebSocket server is a
 * long-running process, and the states it checks are the ones another process wrote meanwhile; it
 * checks them again before it pushes an event to the client, at most once per
 * Subscribers\Client::RECHECK_INTERVAL seconds.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class WebSocketEventsTest extends Tests\Cases\Unit\DbTestCase
{

	// The account of ADMINISTRATOR_TOKEN and the token (sql/dummy.data.sql)
	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string ACCESS_TOKEN = '4f6710a1-38aa-4264-9c0c-b45285181270';

	private const string TOPIC = '/probe/v1/exchange';

	/** @var list<string> */
	private array $sent = [];

	private bool $closed = false;

	private Tests\Support\MovableClock|null $clock = null;

	#[Override]
	public function setUp(): void
	{
		// A clock the test moves, in place of the frozen one, and a topic to subscribe to
		$this->registerNeonConfigurationFile(__DIR__ . '/webSocketEvents.neon');

		parent::setUp();
	}

	/**
	 * @throws Throwable
	 */
	public function testASubscriberOfAnActiveAccountReceivesTheEvents(): void
	{
		$this->subscribe();

		$this->publish('first');
		$this->advance();
		$this->publish('second');

		self::assertSame(['first', 'second'], $this->events());
		self::assertFalse($this->closed);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function accountsThatCannotSignIn(): array
	{
		return [
			'blocked' => [AccountsTypes\AccountState::BLOCKED->value],
			'deleted' => [AccountsTypes\AccountState::DELETED->value],
		];
	}

	/**
	 * Written by another process: this one does not clear its entity manager
	 *
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotSignIn')]
	public function testASubscriberOfAnAccountThatCannotSignInReceivesNothing(string $state): void
	{
		$this->subscribe();

		$this->publish('first');

		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_accounts SET account_state = ? WHERE account_id = ?',
			[$state, Uuid\Uuid::fromString(self::ACCOUNT)->getBytes()],
		);

		$this->advance();
		$this->publish('second');

		self::assertSame(['first'], $this->events());
		self::assertTrue($this->closed);
	}

	/**
	 * @throws Throwable
	 */
	public function testASubscriberWhoseTokenWasRevokedReceivesNothing(): void
	{
		$this->subscribe();

		$this->publish('first');

		$this->getDb()->executeStatement(
			'UPDATE fb_security_tokens SET token_state = ? WHERE token_id = ?',
			[SecurityTypes\TokenState::REVOKED->value, Uuid\Uuid::fromString(self::ACCESS_TOKEN)->getBytes()],
		);

		$this->advance();
		$this->publish('second');

		self::assertSame(['first'], $this->events());
		self::assertTrue($this->closed);
	}

	/**
	 * Blocked through the module, in this process
	 *
	 * @throws Throwable
	 */
	public function testASubscriberOfAnAccountBlockedThroughTheModuleReceivesNothing(): void
	{
		$this->subscribe();

		$this->publish('first');

		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->byId(Uuid\Uuid::fromString(self::ACCOUNT));

		$account = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsRepository::class)
			->findOneBy($findAccount);
		self::assertNotNull($account);

		$this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class)
			->update($account, ['state' => AccountsTypes\AccountState::BLOCKED]);

		$this->advance();
		$this->publish('second');

		self::assertSame(['first'], $this->events());
		self::assertTrue($this->closed);
	}

	/**
	 * Connects a client holding ADMINISTRATOR_TOKEN, checked by the container's client subscriber as
	 * the server checks a new client, and subscribes it through the container's WAMP application
	 * to a topic whose controller does nothing (webSocketEvents.neon, NullWebSocketsController).
	 *
	 * @throws Throwable
	 */
	private function subscribe(): void
	{
		$this->clock = $this->getContainer()->getByType(Tests\Support\MovableClock::class);

		$this->getContainer()->getByType(Controllers\ControllerFactory::class)
			->setMapping(['Probe' => 'FastyBird\\Module\\Accounts\\Tests\\Support\\Null*Controller']);

		$request = (new Handshake\RequestFactory())->createHttpRequest(
			"GET / HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n"
			. 'Authorization: Bearer ' . self::ADMINISTRATOR_TOKEN . "\r\n\r\n",
		);
		self::assertNotNull($request);

		$protocol = $this->getMockBuilder(Encoding\RFC6455::class)
			->onlyMethods(['send', 'close'])
			->getMock();
		$protocol->method('send')->willReturnCallback(function (Entities\Client $client, mixed $payload): void {
			self::assertTrue(is_string($payload));

			$this->sent[] = $payload;
		});
		$protocol->method('close')->willReturnCallback(function (): void {
			$this->closed = true;
		});

		$client = new Entities\WampClient(1, $this->createMock(Socket\ConnectionInterface::class));
		$client->setRequest($request);
		$client->httpHeadersReceived = true;
		$client->setWebSocket(new Entities\WebSocket(true, false, $protocol));

		$this->getContainer()->getByType(Subscribers\Client::class)
			->clientConnected(new Events\ClientConnected($client, $request));

		self::assertNotNull($client->getIdentity());

		$this->getContainer()->getByType(Controllers\WampApplication::class)->handleMessage(
			$client,
			$request,
			Utils\Json::encode([Controllers\WampApplication::MSG_SUBSCRIBE, self::TOPIC]),
		);

		self::assertTrue($this->getContainer()->getByType(Topics\Storage::class)->getTopic(self::TOPIC)->has($client));
		self::assertFalse($this->closed);
	}

	/**
	 * Moves past the interval within which a client's token is not looked up again
	 */
	private function advance(): void
	{
		self::assertNotNull($this->clock);

		$this->clock->advance(Subscribers\Client::RECHECK_INTERVAL);
	}

	/**
	 * Publishes as the modules' sockets bridges do: the topic is fetched from the topics storage
	 *
	 * @throws Throwable
	 */
	private function publish(string $event): void
	{
		$this->getContainer()->getByType(Topics\Storage::class)->getTopic(self::TOPIC)->broadcast($event);
	}

	/**
	 * @return list<string>
	 *
	 * @throws Throwable
	 */
	private function events(): array
	{
		$events = [];

		foreach ($this->sent as $payload) {
			$frame = Utils\Json::decode($payload, forceArrays: true);
			self::assertIsArray($frame);

			if (($frame[0] ?? null) === Controllers\WampApplication::MSG_EVENT) {
				self::assertIsString($frame[2] ?? null);

				$events[] = $frame[2];
			}
		}

		return $events;
	}

}
