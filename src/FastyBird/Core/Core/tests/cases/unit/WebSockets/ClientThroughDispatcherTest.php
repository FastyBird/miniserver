<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FastyBird\Core\Clock;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Subscribers;
use Lcobucci\JWT;
use Nette\DI;
use Override;
use Psr\Log\LoggerInterface;
use React\Socket;
use Stringable;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Subscribers\Client -- the only listener of a WebSockets event anywhere today, on ClientConnected
 * and IncomingMessage -- reached the way production reaches it: registered as a subscriber of the
 * dispatcher, which the container's Wrapper dispatches to through its compiled bridges (census
 * T3, T12-16). ClientAuthenticationTest calls the subscriber's methods directly; this pins that
 * the dispatch actually gets there. E5.6 (#638) re-registers the subscriber on the merged events
 * at priority -10 and must keep both outcomes.
 *
 * The client's token is correctly signed and unexpired but was revoked: the identity provider
 * no longer resolves it, standing in for the accounts module's lookup of the persisted token.
 */
final class ClientThroughDispatcherTest extends BaseTestCase
{

	private const string SIGNATURE = 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAXaVDBmOxCZorqVGRFb';

	private const string ISSUER = 'com.fastybird.miniserver';

	/** @var list<string> */
	private array $warnings = [];

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAClientWithARevokedTokenIsClosedWhenItsConnectIsDispatched(): void
	{
		$this->subscribe();

		$client = $this->client(new Entities\WebSocket(false, false, new Encoding\RFC6455()));

		$this->container->getByType(Server\Wrapper::class)->handleMessage($client, 'headers already received');

		self::assertTrue($client->getWebSocket()->isEstablished());
		self::assertTrue($client->getWebSocket()->isClosing());
		self::assertNull($client->getIdentity());
		self::assertSame(['Client access token is not valid'], $this->warnings);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAMessageFromAClientWithARevokedTokenIsClosedAndNotProcessed(): void
	{
		$this->subscribe();

		$protocol = $this->getMockBuilder(Encoding\RFC6455::class)
			->onlyMethods(['handleMessage'])
			->getMock();
		$protocol->expects(self::never())->method('handleMessage');

		$client = $this->client(new Entities\WebSocket(true, false, $protocol));

		$this->container->getByType(Server\Wrapper::class)
			->handleMessage($client, '[2,"call-1","/devices-module/v1/exchange",{}]');

		self::assertTrue($client->getWebSocket()->isClosing());
		self::assertNull($client->getIdentity());
		self::assertSame(['Client access token is not valid'], $this->warnings);
	}

	/**
	 * Registers the subscriber on the container's dispatcher, as contributte/event-dispatcher does
	 * in production; Core's test container has a plain dispatcher that collects no subscribers.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	private function subscribe(): void
	{
		$clock = new Clock\FrozenClock(new DateTimeImmutable('2026-09-21T13:00:00+00:00'));
		$validator = new Identity\TokenValidator(self::SIGNATURE, self::ISSUER, $clock);

		$revoked = new class implements Identity\IdentityProvider {

			#[Override]
			public function create(JWT\UnencryptedToken $token): Identity\UserIdentity|null
			{
				return null;
			}

		};

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')
			->willReturnCallback(function (string|Stringable $message): void {
				$this->warnings[] = (string) $message;
			});

		// only the per-message path pings the database; the ping's dummy select has to succeed
		$platform = $this->createMock(AbstractPlatform::class);
		$platform->method('getDummySelectSQL')->willReturn('SELECT 1');

		$connection = $this->createMock(Connection::class);
		$connection->method('getDatabasePlatform')->willReturn($platform);

		$entityManager = $this->createMock(EntityManagerInterface::class);
		$entityManager->method('isOpen')->willReturn(true);
		$entityManager->method('getConnection')->willReturn($connection);

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManager')->willReturn($entityManager);

		$this->container->getByType(EventDispatcher\EventDispatcherInterface::class)->addSubscriber(
			new Subscribers\Client(
				new Helpers\Database($managerRegistry),
				new Identity\TokenReader($validator),
				$validator,
				$revoked,
				$logger,
			),
		);
	}

	/**
	 * A real client entity whose handshake carries a correctly signed, unexpired bearer token.
	 *
	 * @throws Throwable
	 */
	private function client(Entities\WebSocket $webSocket): Entities\Client
	{
		$token = (new Identity\TokenBuilder(
			self::SIGNATURE,
			self::ISSUER,
			new Clock\FrozenClock(new DateTimeImmutable('2026-09-21T12:00:00+00:00')),
		))->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user'])->toString();

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

		$client = new Entities\Client(634, $this->createMock(Socket\ConnectionInterface::class));
		$client->setRequest($request);
		$client->setHttpHeadersReceived(true);
		$client->setWebSocket($webSocket);

		return $client;
	}

}
