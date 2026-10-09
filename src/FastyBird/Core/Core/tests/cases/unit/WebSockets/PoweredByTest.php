<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Subscribers;
use Nette\DI;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use React\Socket;
use RuntimeException;
use Throwable;
use function implode;
use function in_array;
use function json_decode;
use function str_contains;
use const JSON_THROW_ON_ERROR;

/**
 * Characterization of where ServerRuntime::VERSION goes on the wire (census T9 row 1a, T12-11):
 * the X-Powered-By header of the handshake response, of the wrapper's own HTTP close, of the 401
 * a rejected client gets and of the application's close, and the agent field of the WAMP welcome.
 * Every assertion but one reads the constant, so #637 changing its value edited no assertion
 * here; what it must not change is that each of these places sends it. The one that does not,
 * testTheServerVersionOnTheWireIsTheFastyBirdWebSocketsAgent(), pins the value itself.
 */
final class PoweredByTest extends BaseTestCase
{

	/** @var list<string> */
	private array $written = [];

	/** @var list<mixed> */
	private array $sent = [];

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheHandshakeResponseAndTheWelcomeCarryTheServerVersion(): void
	{
		$client = $this->client(new Entities\WebSocket(false, false, new Encoding\RFC6455()), $this->handshake());

		$this->container->getByType(Server\Wrapper::class)->handleMessage($client, 'headers already received');

		$response = implode('', $this->written);

		self::assertStringStartsWith('HTTP/1.1 101', $response);
		self::assertStringContainsString("\r\nX-Powered-By: " . Server\ServerRuntime::VERSION . "\r\n", $response);

		self::assertCount(1, $this->sent);
		self::assertIsString($this->sent[0]);

		$welcome = json_decode($this->sent[0], true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($welcome);
		self::assertSame(Controllers\WampApplication::MSG_WELCOME, $welcome[0]);
		self::assertSame(1, $welcome[2]);
		self::assertSame(Server\ServerRuntime::VERSION, $welcome[3]);
	}

	/**
	 * The value itself, as census T9 row 1 sets it (#637): FastyBird's own agent string, on the
	 * handshake response and in the WAMP welcome. The other tests here read the constant, so this
	 * is the one that pins what the clients actually receive.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheServerVersionOnTheWireIsTheFastyBirdWebSocketsAgent(): void
	{
		$client = $this->client(new Entities\WebSocket(false, false, new Encoding\RFC6455()), $this->handshake());

		$this->container->getByType(Server\Wrapper::class)->handleMessage($client, 'headers already received');

		self::assertStringContainsString(
			"\r\nX-Powered-By: FastyBird/WebSockets/1.0.0\r\n",
			implode('', $this->written),
		);

		self::assertCount(1, $this->sent);
		self::assertIsString($this->sent[0]);

		$welcome = json_decode($this->sent[0], true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($welcome);
		self::assertSame('FastyBird/WebSockets/1.0.0', $welcome[3]);
	}

	/**
	 * The wrapper answers a client that fails before its upgrade with its own HTTP close.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheWrappersOwnCloseCarriesTheServerVersion(): void
	{
		$client = $this->client(
			new Entities\WebSocket(false, false, new Encoding\RFC6455()),
			$this->handshake(),
			false,
		);

		$this->container->getByType(Server\Wrapper::class)->handleError($client, new RuntimeException('e5 probe'));

		$response = implode('', $this->written);

		self::assertStringStartsWith('HTTP/1.1 500', $response);
		self::assertTrue(str_contains($response, 'X-Powered-By: ' . Server\ServerRuntime::VERSION));
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testARejectedClientsUnauthorizedResponseCarriesTheServerVersion(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, new Encoding\RFC6455()), $this->handshake());

		$this->container->getByType(Subscribers\Client::class)
			->clientConnected(new Events\ClientConnected($client, $client->getRequest()));

		self::assertSame(['HTTP/1.1 401', 'X-Powered-By:' . Server\ServerRuntime::VERSION], $this->lines());
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheApplicationsCloseCarriesTheServerVersion(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, new Encoding\RFC6455()), $this->handshake());

		$this->container->getByType(Controllers\WampApplication::class)
			->handleError($client, $client->getRequest(), new RuntimeException('e5 probe', 403));

		self::assertSame(['HTTP/1.1 403', 'X-Powered-By:' . Server\ServerRuntime::VERSION], $this->lines());
	}

	/**
	 * The header lines of the one ErrorResponse the client was sent.
	 *
	 * @return list<string>
	 */
	private function lines(): array
	{
		self::assertCount(1, $this->sent);

		$response = $this->sent[0];

		self::assertInstanceOf(Responses\ErrorResponse::class, $response);

		$lines = $response->create();

		self::assertIsArray($lines);
		self::assertTrue(in_array('X-Powered-By:' . Server\ServerRuntime::VERSION, $lines, true));

		$strings = [];

		foreach ($lines as $line) {
			self::assertIsString($line);

			$strings[] = $line;
		}

		return $strings;
	}

	/**
	 * @throws Throwable
	 */
	private function handshake(): Handshake\Request
	{
		$request = (new Handshake\RequestFactory())->createHttpRequest(
			"GET / HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n\r\n",
		);

		self::assertNotNull($request);

		return $request;
	}

	private function client(
		Entities\WebSocket $webSocket,
		Handshake\Request $request,
		bool $headersReceived = true,
	): Entities\Client&MockObject
	{
		$connection = $this->createMock(Socket\ConnectionInterface::class);
		$connection->method('write')
			->willReturnCallback(function (string $data): bool {
				$this->written[] = $data;

				return true;
			});

		$client = $this->createMock(Entities\Client::class);
		$client->method('getId')
			->willReturn(634);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn($headersReceived);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($request);
		$client->method('getConnection')
			->willReturn($connection);
		$client->method('getParameter')
			->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $default);
		$client->method('send')
			->willReturnCallback(function (mixed $response): void {
				$this->sent[] = $response;
			});

		return $client;
	}

}
