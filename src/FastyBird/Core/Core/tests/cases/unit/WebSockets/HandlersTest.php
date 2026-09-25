<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Tests\Fixtures\Dummy\DummyWsConnection;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Clients\Drivers;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Server;
use PHPUnit\Framework\TestCase;
use Psr\Log;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Handlers::handleError() used to build its log context with $client->getRequest(), which throws
 * when the failure happened before the client's request was ever set (the uninitialized typed
 * property case every WebSocket handshake hits). That threw a second exception inside the catch
 * block and the original one -- the one that actually explains the failure -- was never logged.
 * These guard that the original exception always reaches the logger and that handleError() itself
 * never throws.
 */
final class HandlersTest extends TestCase
{

	/**
	 * @throws Throwable
	 */
	public function testHandleErrorLogsTheOriginalExceptionWhenTheClientHasNoRequestYet(): void
	{
		$connection = new DummyWsConnection();

		$client = new Entities\Client(1, $connection);
		// Deliberately never calls setRequest() -- getRequest() throws on the uninitialized
		// typed property, exactly as it does the moment a handshake fails before it completes.

		$storage = new Clients\Storage();
		$storage->setStorageDriver(new Drivers\InMemory());
		$storage->addClient(1, $client);

		$logged = [];

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger->expects(self::once())
			->method('error')
			->willReturnCallback(static function (string $message, array $context = []) use (&$logged): void {
				$logged[] = [$message, $context];
			});

		$wrapper = new Server\Wrapper(
			$this->createMock(Controllers\Dispatcher::class),
			$this->createMock(Clients\IStorage::class),
		);

		$wsApplication = $this->createMock(Server\ServerWrapper::class);
		$wsApplication->expects(self::once())
			->method('handleError')
			->with($client, self::isInstanceOf(RuntimeException::class));

		$handlers = new Server\Handlers(
			$wrapper,
			new Server\FlashWrapper(),
			$storage,
			$this->createMock(Clients\ClientProvider::class),
			$logger,
		);

		$method = new ReflectionMethod(Server\Handlers::class, 'handleError');

		$method->invoke($handlers, new RuntimeException('original failure'), $connection, $wsApplication);

		self::assertCount(1, $logged);
		self::assertSame('original failure', $logged[0][0]);
		self::assertArrayNotHasKey('request', $logged[0][1]);
	}

	/**
	 * @throws Throwable
	 */
	public function testHandleErrorFallbackKeepsTheOriginalExceptionMessageAndEndsTheConnection(): void
	{
		$connection = new DummyWsConnection();

		// No driver is attached, so Storage::getClient() itself throws and the outer catch runs.
		$storage = new Clients\Storage();

		$logged = [];

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger->expects(self::once())
			->method('error')
			->willReturnCallback(static function (string $message, array $context = []) use (&$logged): void {
				$logged[] = [$message, $context];
			});

		$wrapper = new Server\Wrapper(
			$this->createMock(Controllers\Dispatcher::class),
			$this->createMock(Clients\IStorage::class),
		);

		$wsApplication = $this->createMock(Server\ServerWrapper::class);
		$wsApplication->expects(self::never())
			->method('handleError');

		$handlers = new Server\Handlers(
			$wrapper,
			new Server\FlashWrapper(),
			$storage,
			$this->createMock(Clients\ClientProvider::class),
			$logger,
		);

		$method = new ReflectionMethod(Server\Handlers::class, 'handleError');

		$method->invoke($handlers, new RuntimeException('original failure'), $connection, $wsApplication);

		self::assertTrue($connection->ended);
		self::assertCount(1, $logged);
		self::assertNotSame('original failure', $logged[0][0]);
		self::assertSame('original failure', $logged[0][1]['previous'] ?? null);
	}

}
