<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Http;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Response;
use FastyBird\Core\Http\ServerResponse;
use FastyBird\Core\Http\Stream;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function json_decode;

final class ResponseTest extends TestCase
{

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testJsonSetsJsonContentTypeAndRoundTripsTheBody(): void
	{
		$response = Response::json(['foo' => 'bar', 'count' => 3]);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
		self::assertSame(
			['foo' => 'bar', 'count' => 3],
			json_decode((string) $response->getBody(), true),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	public function testTextHtmlAndXmlDefaultToStatus200WithTheirOwnContentType(): void
	{
		$text = Response::text('hi');
		$html = Response::html('<p>hi</p>');
		$xml = Response::xml('<hi/>');

		self::assertSame(200, $text->getStatusCode());
		self::assertSame('text/plain', $text->getHeaderLine('Content-Type'));

		self::assertSame(200, $html->getStatusCode());
		self::assertSame('text/html', $html->getHeaderLine('Content-Type'));

		self::assertSame(200, $xml->getStatusCode());
		self::assertSame('application/xml', $xml->getHeaderLine('Content-Type'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testNotFoundProducesStatus404(): void
	{
		self::assertSame(404, Response::notFound()->getStatusCode());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testRedirectProducesA3xxStatusAndLocationHeader(): void
	{
		$response = Response::redirect('https://example.test/target');

		self::assertSame(303, $response->getStatusCode());
		self::assertGreaterThanOrEqual(300, $response->getStatusCode());
		self::assertLessThan(400, $response->getStatusCode());
		self::assertSame('https://example.test/target', $response->getHeaderLine('Location'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testRedirectRejectsAStatusBelowTheThreeHundredRange(): void
	{
		self::expectException(Exceptions\InvalidArgument::class);

		Response::redirect('https://example.test/target', 299);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testRedirectRejectsAStatusAboveTheThreeHundredRange(): void
	{
		self::expectException(Exceptions\InvalidArgument::class);

		Response::redirect('https://example.test/target', 400);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testRedirectAcceptsAValidNonDefaultStatus(): void
	{
		self::assertSame(301, Response::redirect('https://example.test/target', 301)->getStatusCode());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testWithHeaderReturnsANewInstanceAndLeavesTheOriginalUntouched(): void
	{
		$original = Response::text('original');
		$modified = $original->withHeader('X-Test', 'value');

		self::assertNotSame($original, $modified);
		self::assertSame('value', $modified->getHeaderLine('X-Test'));
		self::assertSame('', $original->getHeaderLine('X-Test'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testWithStatusReturnsANewInstanceAndLeavesTheOriginalUntouched(): void
	{
		$original = Response::text('original');
		$modified = $original->withStatus(201);

		self::assertNotSame($original, $modified);
		self::assertSame(201, $modified->getStatusCode());
		self::assertSame(200, $original->getStatusCode());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testWithBodyReturnsANewInstanceAndLeavesTheOriginalUntouched(): void
	{
		$original = Response::text('original');
		$modified = $original->withBody(Stream::fromBodyString('replaced'));

		self::assertNotSame($original, $modified);
		self::assertSame('replaced', (string) $modified->getBody());
		self::assertSame('original', (string) $original->getBody());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	public function testWithProtocolVersionReturnsANewInstanceAndLeavesTheOriginalUntouched(): void
	{
		$original = Response::text('original');
		$modified = $original->withProtocolVersion('1.0');

		self::assertNotSame($original, $modified);
		self::assertSame('1.0', $modified->getProtocolVersion());
		self::assertSame('1.1', $original->getProtocolVersion());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testWithoutHeaderRemovesAHeaderThatWithHeaderAdded(): void
	{
		$withHeader = Response::text('original')->withHeader('X-Test', 'value');
		$withoutHeader = $withHeader->withoutHeader('X-Test');

		self::assertTrue($withHeader->hasHeader('X-Test'));
		self::assertFalse($withoutHeader->hasHeader('X-Test'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testWithAddedHeaderAppendsRatherThanReplaces(): void
	{
		$original = Response::text('original')->withHeader('X-Test', 'first');
		$appended = $original->withAddedHeader('X-Test', 'second');

		self::assertSame(['first', 'second'], $appended->getHeader('X-Test'));
		self::assertSame(['first'], $original->getHeader('X-Test'));
	}

	/**
	 * @throws RuntimeException
	 */
	public function testStreamFromBodyStringReportsSizeContentsAndStringRepresentation(): void
	{
		$stream = Stream::fromBodyString('abc');

		self::assertSame(3, $stream->getSize());
		self::assertSame('abc', $stream->getContents());
		self::assertSame('abc', (string) $stream);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testServerResponseWithAttributeIsImmutableAndAttributeAccessorsReportPresence(): void
	{
		$original = new ServerResponse(200, Stream::fromBodyString('body'));
		$modified = $original->withAttribute('foo', 'bar');

		self::assertNotSame($original, $modified);
		self::assertTrue($modified->hasAttribute('foo'));
		self::assertSame('bar', $modified->getAttribute('foo'));
		self::assertFalse($original->hasAttribute('foo'));
	}

}
