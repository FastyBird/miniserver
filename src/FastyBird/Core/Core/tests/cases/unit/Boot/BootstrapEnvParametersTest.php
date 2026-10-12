<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Boot;

use FastyBird\Core\Boot;
use FastyBird\Core\Exceptions;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_key_exists;
use function getenv;
use function putenv;
use const PHP_INT_MAX;

/**
 * The process running the tests may carry real FB_APP_PARAMETER__* variables (CI sets the database
 * and security ones), so every case asserts on the leaves it set and never on the whole array, and
 * every variable it touches is restored afterwards.
 */
final class BootstrapEnvParametersTest extends TestCase
{

	private const string PREFIX = 'FB_APP_PARAMETER_';

	/** @var array<string, string|false> */
	private array $original = [];

	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->original as $name => $value) {
			if ($value === false) {
				unset($_ENV[$name]);
				putenv($name);
			} else {
				$_ENV[$name] = $value;
				putenv($name . '=' . $value);
			}
		}

		$this->original = [];

		parent::tearDown();
	}

	/**
	 * @return array<string, array{string, bool|int|string}>
	 */
	public static function typedValues(): array
	{
		return [
			'true' => ['true', true],
			'false' => ['false', false],
			'TRUE' => ['TRUE', true],
			'FALSE' => ['FALSE', false],
			'True' => ['True', true],
			'False' => ['False', false],
			'zero' => ['0', 0],
			'one' => ['1', 1],
			'positive integer' => ['3306', 3_306],
			'negative integer' => ['-5', -5],
			'negative one' => ['-1', -1],
			'largest integer' => [(string) PHP_INT_MAX, PHP_INT_MAX],
			'leading zero' => ['0123', '0123'],
			'two zeros' => ['00', '00'],
			'negative leading zero' => ['-0123', '-0123'],
			'negative zero' => ['-0', '-0'],
			'explicit plus sign' => ['+5', '+5'],
			'float' => ['1.5', '1.5'],
			'negative float' => ['-1.5', '-1.5'],
			'float with zero fraction' => ['2.0', '2.0'],
			'leading dot' => ['.5', '.5'],
			'exponent' => ['1e3', '1e3'],
			'hexadecimal' => ['0x1A', '0x1A'],
			'version' => ['10.11', '10.11'],
			'padded' => [' 12', ' 12'],
			'trailing newline' => ["12\n", "12\n"],
			'beyond the largest integer' => ['9223372036854775808', '9223372036854775808'],
			'a lone minus' => ['-', '-'],
			'empty string' => ['', ''],
			'word' => ['yes', 'yes'],
			'host' => ['127.0.0.1', '127.0.0.1'],
		];
	}

	/**
	 * @return array<string, array{string, string, string}>
	 */
	public static function protectedNames(): array
	{
		return [
			'password' => ['FB_APP_PARAMETER__DATABASE_PASSWORD', 'database', 'password'],
			'username' => ['FB_APP_PARAMETER__DATABASE_USERNAME', 'database', 'username'],
			'signature' => ['FB_APP_PARAMETER__SECURITY_SIGNATURE', 'security', 'signature'],
			'dsn' => ['FB_APP_PARAMETER__SENTRY_DSN', 'sentry', 'dsn'],
			'key' => ['FB_APP_PARAMETER__API_KEY', 'api', 'key'],
			'redis password' => ['FB_APP_PARAMETER__REDIS_PASSWORD', 'redis', 'password'],
			'probe password' => ['FB_APP_PARAMETER__PROBE_PASSWORD', 'probe', 'password'],
			'mixed case suffix' => ['FB_APP_PARAMETER__PROBE_SecretKey', 'probe', 'secretkey'],
		];
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function protectedValues(): array
	{
		return [
			'all digits' => ['12345678'],
			'canonical integer' => ['3306'],
			'zero' => ['0'],
			'negative integer' => ['-5'],
			'true' => ['true'],
			'false' => ['FALSE'],
			'float' => ['1.5'],
			'leading zero' => ['0123'],
			'empty string' => [''],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	#[DataProvider('typedValues')]
	public function testValuesAreTyped(string $value, bool|int|string $expected): void
	{
		$this->setEnvironment('FB_APP_PARAMETER__PROBE_VALUE', $value);

		$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

		self::assertSame($expected, $parameters['probe']['value']);
	}

	/**
	 * The shipped `api.prefixed.modules` switch, which an operator turns off with `false`
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testFalseIsNotTrue(): void
	{
		$this->setEnvironment('FB_APP_PARAMETER__API_PREFIXED_MODULES', 'false');

		$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

		self::assertFalse($parameters['api']['prefixed']['modules']);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	#[DataProvider('protectedValues')]
	public function testCredentialsAreNeverCast(string $value): void
	{
		foreach (self::protectedNames() as [$name, $section, $key]) {
			$this->setEnvironment($name, $value);

			$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

			self::assertSame($value, $parameters[$section][$key], $name);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testAnAllDigitDatabasePasswordStaysAString(): void
	{
		$this->setEnvironment('FB_APP_PARAMETER__DATABASE_PASSWORD', '12345678');

		$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

		self::assertSame('12345678', $parameters['database']['password']);
	}

	/**
	 * PASSWORD is a suffix of the name, not a word of it: a name that merely contains one is typed
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testOnlyASuffixProtectsAName(): void
	{
		$this->setEnvironment('FB_APP_PARAMETER__PROBE_PASSWORD_AGE', '30');

		$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

		self::assertSame(30, $parameters['probe']['password']['age']);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testNamesBecomeNestedLowercaseKeys(): void
	{
		$this->setEnvironment('FB_APP_PARAMETER__DATABASE_HOST', 'database');
		$this->setEnvironment('FB_APP_PARAMETER__DATABASE_PORT', '3306');
		$this->setEnvironment('FB_APP_PARAMETER__MODULES_EXCHANGE', 'false');

		$parameters = Boot\Bootstrap::loadEnvParameters(self::PREFIX);

		self::assertSame('database', $parameters['database']['host']);
		self::assertSame(3_306, $parameters['database']['port']);
		self::assertFalse($parameters['modules']['exchange']);
		self::assertFalse(array_key_exists('DATABASE', $parameters));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testAnEmptyDelimiterIsRejected(): void
	{
		$this->expectException(Exceptions\InvalidArgument::class);

		Boot\Bootstrap::loadEnvParameters(self::PREFIX, '');
	}

	private function setEnvironment(string $name, string $value): void
	{
		if (!array_key_exists($name, $this->original)) {
			$this->original[$name] = getenv($name);
		}

		$_ENV[$name] = $value;
		putenv($name . '=' . $value);
	}

}
