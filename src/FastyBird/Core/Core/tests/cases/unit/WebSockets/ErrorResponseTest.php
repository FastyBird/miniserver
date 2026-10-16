<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Entities;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use React\Socket;
use Throwable;
use TypeError;

/**
 * The WebSockets error response takes its headers as an array, and a client keeps its parameters
 * as an array (#723): an ArrayHash is no longer a way in
 */
final class ErrorResponseTest extends TestCase
{

	/**
	 * @throws Throwable
	 */
	public function testHeadersAreWrittenAsLinesInTheOrderTheyWereGiven(): void
	{
		$response = new Responses\ErrorResponse(401, ['X-Powered-By' => 'FastyBird', 'Retry-After' => 5]);

		self::assertSame(['HTTP/1.1 401', 'X-Powered-By:FastyBird', 'Retry-After:5'], $response->create());

		$response->addHeader('X-Powered-By', 'Other');
		$response->addHeader('Via', 'proxy');

		self::assertSame(['HTTP/1.1 401', 'X-Powered-By:Other', 'Retry-After:5', 'Via:proxy'], $response->create());
		self::assertSame(Utils\Json::encode($response->create()), (string) $response);
	}

	/**
	 * @throws Throwable
	 */
	public function testSettingHeadersReplacesTheOnesGivenBefore(): void
	{
		$response = new Responses\ErrorResponse(404, ['X-One' => '1']);
		$response->setHeaders(['X-Two' => '2']);

		self::assertSame(['HTTP/1.1 404', 'X-Two:2'], $response->create());
		self::assertSame(['HTTP/1.1 500'], (new Responses\ErrorResponse(500))->create());
		self::assertSame(['HTTP/1.1 500'], (new Responses\ErrorResponse(500, []))->create());
	}

	/**
	 * @throws Throwable
	 */
	public function testAStatusOutOfRangeIsRefused(): void
	{
		$this->expectException(CoreExceptions\InvalidArgument::class);

		new Responses\ErrorResponse(99);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayHashOfHeadersIsRefused(): void
	{
		$this->expectException(TypeError::class);

		// @phpstan-ignore argument.type (the call is the test: the headers are an array)
		new Responses\ErrorResponse(401, Utils\ArrayHash::from(['X-Powered-By' => 'FastyBird']));
	}

	/**
	 * A parameter that was added with a null value is still there: it is not replaced by the
	 * default the caller asks for
	 *
	 * @throws Throwable
	 */
	public function testAClientKeepsItsParametersIncludingANullOne(): void
	{
		$connection = $this->createMock(Socket\ConnectionInterface::class);
		$connection->method('getRemoteAddress')->willReturn('tcp://127.0.0.1:9000');

		$client = new Entities\Client(1, $connection);

		self::assertSame('fallback', $client->getParameter('topic', 'fallback'));

		$client->addParameter('topic', 'devices');
		$client->addParameter('limit', null);

		self::assertSame('devices', $client->getParameter('topic', 'fallback'));
		self::assertNull($client->getParameter('limit', 'fallback'));
	}

}
