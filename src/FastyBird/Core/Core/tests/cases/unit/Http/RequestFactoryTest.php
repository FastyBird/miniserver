<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Http;

use FastyBird\Core\WebSockets\Handshake\RequestFactory;
use FastyBird\Core\WebSockets\Handshake;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * RequestFactory::createHttpRequest() passes a raw-body callback into Request::__construct().
 * Request used to declare that parameter as the bare `null` type instead of `callable|null`,
 * which made every WebSocket handshake fail with a TypeError before a single byte was logged.
 */
final class RequestFactoryTest extends TestCase
{

	/**
	 * @throws Throwable
	 */
	public function testCreateHttpRequestBuildsARequestFromARawWebSocketUpgradeAndExposesTheRawBody(): void
	{
		$packet = "GET /wamp HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n"
			. "\r\n"
			. 'buffered-body';

		$factory = new Handshake\RequestFactory();

		$request = $factory->createHttpRequest($packet);

		self::assertNotNull($request);
		self::assertSame('GET', $request->getMethod());
		self::assertSame('buffered-body', $request->getRawBody());
	}

}
