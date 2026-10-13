<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Logging;

use DateTimeImmutable;
use Doctrine\Persistence;
use FastyBird\Core\Boot;
use FastyBird\Core\Clock;
use FastyBird\Core\Logging;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models;
use Override;
use PHPUnit\Framework\TestCase;
use React\Http\Message\ServerRequest;
use RuntimeException;
use Throwable;
use Tracy;
use function array_diff;
use function array_map;
use function assert;
use function basename;
use function file_get_contents;
use function glob;
use function hash;
use function implode;
use function is_dir;
use function is_string;
use function mkdir;
use function str_contains;
use function str_starts_with;
use function substr_count;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const GLOB_BRACE;

/**
 * What Core writes about an error keeps the request's credentials out: the API key, its hash and
 * the bearer token, from $_SERVER, from the request object in the stack and from the arguments
 * they are passed as
 */
final class SensitiveDataTest extends TestCase
{

	private const string API_KEY = 'b3a1f0c2d4e6f8a0b2c4d6e8f0a2b4c6d8e0f2a4b6c8d0e2f4a6b8c0d2e4f6a8';

	private const string BEARER = 'eyJhbGciOiJIUzI1NiJ9.PROBEBEARERPAYLOAD0123456789.PROBEBEARERSIGNATURE';

	/** @var array<mixed> */
	private array $server = [];

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->server = $_SERVER;

		// As php-fpm hands them over
		$_SERVER['HTTP_X_API_KEY'] = self::API_KEY;
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . self::BEARER;
		$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer ' . self::BEARER;

		if (!is_dir(FB_LOGS_DIR)) {
			mkdir(FB_LOGS_DIR, 0777, true);
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		$_SERVER = $this->server;

		parent::tearDown();
	}

	/**
	 * The key's lookup fails; the stack holds the request, the hash and, in $_SERVER, the key
	 *
	 * @throws Throwable
	 */
	public function testLoggedBlueScreenOfAnApiKeyRequestHidesTheKey(): void
	{
		$managerRegistry = $this->createMock(Persistence\ManagerRegistry::class);
		// Thrown from inside the lookup, so its stack holds the frames that carry the credentials
		$managerRegistry->method('getRepository')->willReturnCallback(static function (): never {
			throw new RuntimeException('Database is gone');
		});

		$reader = new Identity\ApiKeyReader(
			new Models\ApiKeys\Repository($managerRegistry),
			new Clock\FrozenClock(new DateTimeImmutable('2026-10-13T12:00:00+00:00')),
		);

		try {
			$reader->read(new ServerRequest('GET', '/api/v1/devices', [
				'X-Api-Key' => self::API_KEY,
				'Authorization' => 'Bearer ' . self::BEARER,
			]));

			self::fail('The lookup did not fail');
		} catch (RuntimeException $ex) {
			$this->assertLoggedWithoutCredentials($ex, __FUNCTION__);
		}
	}

	/**
	 * The bearer token fails validation; the stack holds the request, the header and the token
	 *
	 * @throws Throwable
	 */
	public function testLoggedBlueScreenOfABearerRequestHidesTheToken(): void
	{
		$reader = new Identity\TokenReader(new Identity\TokenValidator(
			'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAXaVDBmOxCZorqVGRFb',
			'com.fastybird.miniserver',
			new Clock\FrozenClock(new DateTimeImmutable('2026-10-13T12:00:00+00:00')),
		));

		try {
			$reader->read(new ServerRequest('GET', '/api/v1/devices', [
				'X-Api-Key' => self::API_KEY,
				'Authorization' => 'Bearer ' . self::BEARER,
			]));

			self::fail('The token was accepted');
		} catch (Throwable $ex) {
			$this->assertLoggedWithoutCredentials($ex, __FUNCTION__);
		}
	}

	/**
	 * Boot configures the blue screen Tracy shows and logs with itself, and Tracy's own logger
	 * writes it without the markdown report
	 *
	 * @throws Throwable
	 */
	public function testTracysBlueScreenAndLoggerHideTheCredentials(): void
	{
		Boot\Bootstrap::boot();

		$blueScreen = Tracy\Debugger::getBlueScreen();

		foreach (['x-api-key', 'http_x_api_key', 'http_authorization', 'authorization'] as $key) {
			self::assertContains($key, $blueScreen->keysToHide);
		}

		$directory = sys_get_temp_dir() . '/' . uniqid('fb-sensitive-data-', true);
		mkdir($directory);

		$logger = new Logging\TracyLogger($directory);
		$logger->log(new RuntimeException('Logged by Tracy ' . uniqid()), Tracy\ILogger::EXCEPTION);

		// The HTML blue screen, and no markdown report beside it; exception.log is the log line
		$files = glob($directory . '/*.{html,md}', GLOB_BRACE);
		assert($files !== false);

		self::assertCount(1, $files, implode(', ', $files));
		$this->assertNoCredentials($files[0]);
	}

	/**
	 * @throws Throwable
	 */
	private function assertLoggedWithoutCredentials(Throwable $ex, string $testMethod): void
	{
		$before = $this->logFiles();

		Logging\Logger::buildException($ex);

		$written = $this->blueScreensOf($before, $testMethod);

		// The HTML blue screen, and no markdown report beside it
		self::assertCount(1, $written, implode(', ', $written));
		self::assertStringEndsWith('.html', $written[0]);

		$this->assertNoCredentials($written[0]);

		// It is the stack that carried them: the request object is in it
		$content = file_get_contents($written[0]);
		assert(is_string($content));
		self::assertTrue(str_contains($content, 'ServerRequest'), 'The request is not in the stack');

		array_map(unlink(...), $written);
	}

	/**
	 * The blue screens and reports written since $before for this test's exception: the test's
	 * own method is in its stack. The log directory is shared by the tests running in parallel,
	 * so files of other tests' errors may appear meanwhile
	 *
	 * @param list<string> $before
	 *
	 * @return list<string>
	 */
	private function blueScreensOf(array $before, string $testMethod): array
	{
		$mine = [];

		foreach (array_diff($this->logFiles(), $before) as $file) {
			if (!str_starts_with(basename($file), 'exception')) {
				continue;
			}

			$content = file_get_contents($file);

			if (is_string($content) && str_contains($content, $testMethod)) {
				$mine[] = $file;
			}
		}

		return $mine;
	}

	/**
	 * @return list<string>
	 */
	private function logFiles(): array
	{
		$files = glob(FB_LOGS_DIR . '/*');

		return $files === false ? [] : $files;
	}

	private function assertNoCredentials(string $file): void
	{
		$content = file_get_contents($file);
		assert(is_string($content));

		self::assertSame(0, substr_count($content, self::API_KEY), 'the API key');
		self::assertSame(0, substr_count($content, hash('sha256', self::API_KEY)), 'the hash of the API key');
		self::assertSame(0, substr_count($content, 'PROBEBEARERPAYLOAD0123456789'), 'the bearer token');
	}

}
