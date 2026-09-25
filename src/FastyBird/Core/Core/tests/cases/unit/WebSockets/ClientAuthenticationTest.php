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
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Subscribers;
use Lcobucci\JWT;
use Override;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\Socket;
use Stringable;
use Throwable;
use function in_array;
use function json_encode;
use function str_contains;

/**
 * The WebSocket handshake has to authenticate the client with the same services and the same
 * criteria the HTTP side uses: a bearer header is read by TokenReader exactly as the JSON:API
 * user middleware reads it, a "token" cookie is validated exactly as the presenter subscriber
 * validates it, and either has to resolve to an identity through IIdentityFactory -- the seam
 * the accounts module uses to require that the token is still persisted (issued, not revoked).
 * It used to accept any value at all, as long as a header or cookie was present.
 */
final class ClientAuthenticationTest extends TestCase
{

	private const string SIGNATURE = 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAXaVDBmOxCZorqVGRFb';

	private const string ISSUER = 'com.fastybird.miniserver';

	private const string NOW = '2026-09-21T12:00:00+00:00';

	private const string USER = '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a';

	/** @var list<array{message: string, context: array<mixed>}> */
	private array $logged = [];

	/**
	 * @param non-empty-string $signature
	 *
	 * @throws Throwable
	 */
	private function issue(DateTimeImmutable|null $expiration = null, string $signature = self::SIGNATURE): string
	{
		$builder = new Identity\TokenBuilder(
			$signature,
			self::ISSUER,
			new Clock\FrozenClock(new DateTimeImmutable(self::NOW)),
		);

		return $builder->build(self::USER, ['user'], $expiration)->toString();
	}

	/**
	 * @param list<string> $persistedTokens tokens the identity factory resolves, standing in for
	 *                                      the accounts module's lookup of the persisted token
	 *
	 * @throws Throwable
	 */
	private function subscriber(array $persistedTokens, bool $configured = true): Subscribers\Client
	{
		$validator = new Identity\TokenValidator(
			self::SIGNATURE,
			self::ISSUER,
			new Clock\FrozenClock(new DateTimeImmutable('2026-09-21T13:00:00+00:00')),
		);

		$identityFactory = new class ($persistedTokens, self::USER) implements Identity\IdentityProvider {

			/**
			 * @param list<string> $persistedTokens
			 */
			public function __construct(private readonly array $persistedTokens, private readonly string $userId)
			{
			}

			/**
			 * @throws Throwable
			 */
			#[Override]
			public function create(JWT\UnencryptedToken $token): Identity\UserIdentity|null
			{
				return in_array($token->toString(), $this->persistedTokens, true)
					? new Identity\PlainIdentity($this->userId, ['user'])
					: null;
			}

		};

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			function (string|Stringable $message, array $context = []): void {
				$this->logged[] = ['message' => (string) $message, 'context' => $context];
			},
		);

		// Only the per-message path pings the database, before it re-checks the token; the
		// ping's dummy select just has to succeed
		$platform = $this->createMock(AbstractPlatform::class);
		$platform->method('getDummySelectSQL')->willReturn('SELECT 1');

		$connection = $this->createMock(Connection::class);
		$connection->method('getDatabasePlatform')->willReturn($platform);

		$entityManager = $this->createMock(EntityManagerInterface::class);
		$entityManager->method('isOpen')->willReturn(true);
		$entityManager->method('getConnection')->willReturn($connection);

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManager')->willReturn($entityManager);

		$database = new Helpers\Database($managerRegistry);

		return $configured
			? new Subscribers\Client(
				$database,
				new Identity\TokenReader($validator),
				$validator,
				$identityFactory,
				$logger,
			)
			: new Subscribers\Client($database, null, null, null, $logger);
	}

	/**
	 * Drives an incoming frame through the server wrapper with the subscriber registered the way
	 * CoreExtension registers it, over a real client entity and a real RFC6455 protocol, so
	 * rejection goes through the actual closeSession() -> IClient::close() -> protocol close.
	 * Only the protocol's frame handling -- the step that reaches the WAMP application and its
	 * controllers -- is replaced, to count whether the frame got there.
	 *
	 * @throws Throwable
	 */
	private function deliverFrame(
		Subscribers\Client $subscriber,
		Handshake\IRequest $request,
		int $expectedDeliveries,
	): Entities\IWebSocket
	{
		$protocol = $this->getMockBuilder(Encoding\RFC6455::class)
			->onlyMethods(['handleMessage'])
			->getMock();
		$protocol->expects(self::exactly($expectedDeliveries))->method('handleMessage');

		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = new Entities\Client(1, $this->createMock(Socket\ConnectionInterface::class));
		$client->setRequest($request);
		$client->setHttpHeadersReceived(true);
		$client->setWebSocket($webSocket);

		$wrapper = new Server\Wrapper(
			$this->createMock(Controllers\Dispatcher::class),
			$this->createMock(Clients\IStorage::class),
		);
		$wrapper->onIncomingMessage[] = static function (
			Entities\ConnectedClient $client,
			Handshake\IRequest $request,
		) use ($subscriber): void {
			$subscriber->incomingMessage(new Events\IncomingMessage($client, $request));
		};

		$wrapper->handleMessage($client, '[2,"call-1","/devices-module/v1/exchange",{}]');

		return $webSocket;
	}

	/**
	 * @param array<string, string> $headers
	 *
	 * @throws Throwable
	 */
	private function handshake(array $headers): Handshake\IRequest
	{
		$packet = "GET / HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n";

		foreach ($headers as $name => $value) {
			$packet .= $name . ': ' . $value . "\r\n";
		}

		$request = (new Handshake\RequestFactory())->createHttpRequest($packet . "\r\n");

		self::assertNotNull($request);

		return $request;
	}

	/**
	 * @throws Throwable
	 */
	private function acceptedClient(): Entities\ConnectedClient
	{
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->expects(self::never())->method('send');
		$client->expects(self::never())->method('close');

		return $client;
	}

	/**
	 * @throws Throwable
	 */
	private function rejectedClient(): Entities\ConnectedClient
	{
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->expects(self::once())
			->method('send')
			->with(self::callback(
				static fn (mixed $response): bool => $response instanceof Responses\ErrorResponse
					&& ($response->create()[0] ?? null) === 'HTTP/1.1 401',
			));
		$client->expects(self::once())->method('close');

		return $client;
	}

	/**
	 * @throws Throwable
	 */
	public function testAValidPersistedBearerTokenIsAccepted(): void
	{
		$token = $this->issue();

		$client = $this->acceptedClient();

		$accepted = $this->subscriber([$token])
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertTrue($accepted);
	}

	/**
	 * @throws Throwable
	 */
	public function testAGarbageBearerTokenIsRejected(): void
	{
		$client = $this->rejectedClient();

		$this->subscriber([])->clientConnected(new Events\ClientConnected(
			$client,
			$this->handshake(['Authorization' => 'Bearer totally-garbage-not-a-jwt']),
		));
	}

	/**
	 * @throws Throwable
	 */
	public function testABearerTokenSignedWithAnotherSignatureIsRejected(): void
	{
		$token = $this->issue(null, 'Nq7ZBvAaP2sXtYuEwR5cV8bN1mK4jH6gF9dS3aQ0zL');

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([$token])
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnExpiredBearerTokenIsRejected(): void
	{
		// Issued at 12:00, valid till 12:30; the validator's clock reads 13:00
		$token = $this->issue(new DateTimeImmutable('2026-09-21T12:30:00+00:00'));

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([$token])
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * The JSON:API reads no token at all from an authorization header without the bearer
	 * prefix, which leaves the request anonymous; on the WebSocket that is a rejection.
	 *
	 * @throws Throwable
	 */
	public function testAValidTokenWithoutTheBearerPrefixIsRejected(): void
	{
		$token = $this->issue();

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([$token])
			->checkSecurity($client, $this->handshake(['Authorization' => $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * Correctly signed and unexpired, but the identity factory does not know it -- never
	 * issued by this installation, or deleted on sign-out.
	 *
	 * @throws Throwable
	 */
	public function testAnUnknownOrRevokedBearerTokenIsRejected(): void
	{
		$token = $this->issue();

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([])
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * @throws Throwable
	 */
	public function testAMissingTokenIsRejected(): void
	{
		$client = $this->rejectedClient();

		$accepted = $this->subscriber([])->checkSecurity($client, $this->handshake([]), [], []);

		self::assertFalse($accepted);
		self::assertSame('Client access token is missing', $this->logged[0]['message'] ?? null);
	}

	/**
	 * @throws Throwable
	 */
	public function testAValidPersistedCookieTokenIsAccepted(): void
	{
		$token = $this->issue();

		$client = $this->acceptedClient();

		$accepted = $this->subscriber([$token])
			->checkSecurity($client, $this->handshake(['Cookie' => 'token=' . $token]), [], []);

		self::assertTrue($accepted);
	}

	/**
	 * @throws Throwable
	 */
	public function testAGarbageCookieTokenIsRejected(): void
	{
		$client = $this->rejectedClient();

		$accepted = $this->subscriber([])
			->checkSecurity($client, $this->handshake(['Cookie' => 'token=totally-garbage-not-a-jwt']), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnUnknownOrRevokedCookieTokenIsRejected(): void
	{
		$token = $this->issue();

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([])
			->checkSecurity($client, $this->handshake(['Cookie' => 'token=' . $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * Without a configured signature Core registers no token services, and without the
	 * accounts module nothing provides identities; nothing can be validated, so nothing passes.
	 *
	 * @throws Throwable
	 */
	public function testEveryTokenIsRejectedWhenAuthenticationIsNotConfigured(): void
	{
		$token = $this->issue();

		$client = $this->rejectedClient();

		$accepted = $this->subscriber([$token], false)
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertFalse($accepted);
	}

	/**
	 * The token is re-checked on every incoming message. Once it no longer resolves -- here it
	 * was revoked after the handshake -- the client is closed and the message that triggered
	 * the check must not be processed: it could be a property SET.
	 *
	 * @throws Throwable
	 */
	public function testAMessageFromAClientWhoseTokenWasRevokedIsNotProcessed(): void
	{
		$token = $this->issue();

		$webSocket = $this->deliverFrame(
			$this->subscriber([]),
			$this->handshake(['Authorization' => 'Bearer ' . $token]),
			0,
		);

		self::assertTrue($webSocket->isClosing());
	}

	/**
	 * @throws Throwable
	 */
	public function testAMessageFromAnAuthenticatedClientIsProcessed(): void
	{
		$token = $this->issue();

		$webSocket = $this->deliverFrame(
			$this->subscriber([$token]),
			$this->handshake(['Authorization' => 'Bearer ' . $token]),
			1,
		);

		self::assertFalse($webSocket->isClosing());
	}

	/**
	 * @throws Throwable
	 */
	public function testTheRejectionIsLoggedWithoutTheTokenValue(): void
	{
		$token = $this->issue();

		$client = $this->rejectedClient();

		$this->subscriber([])
			->checkSecurity($client, $this->handshake(['Authorization' => 'Bearer ' . $token]), [], []);

		self::assertCount(1, $this->logged);
		self::assertSame('Client access token is not valid', $this->logged[0]['message']);

		$record = json_encode($this->logged[0]);

		self::assertIsString($record);
		self::assertFalse(str_contains($record, $token));
	}

}
