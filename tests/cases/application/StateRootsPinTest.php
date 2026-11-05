<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_keys;
use function count;
use function escapeshellarg;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function shell_exec;
use function sort;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * What the states that extend the RedisDb plugin's State map to, pinned (#724 PR4d). The production container is
 * booted in a child process (bootstrap-state-roots.php) and every concrete state of every package that extends the
 * plugin's State is given the same inputs, the JSON text the plugin's factory reads from Redis and the arrays with
 * objects the property managers hand to the mapper. What each made, the JSON the API encodes and serialize() of the
 * state, or that it was refused, has to be what fixtures/state-roots.json holds: the output of the same script on
 * 2e495de6c, where orisai still mapped all of them
 */
final class StateRootsPinTest extends TestCase
{

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryStateIsWhatOrisaiMade(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/state-roots.json'));
		$actual = self::decode(self::execute());

		// The classes first: a state that went missing, or a new one, is not a difference of its inputs
		$expected = array_keys($pinned);
		$found = array_keys($actual);

		sort($expected);
		sort($found);

		self::assertSame($expected, $found);
		self::assertGreaterThanOrEqual(6, count($found));

		$refused = 0;
		$checked = 0;

		foreach ($pinned as $class => $cases) {
			self::assertSame(array_keys($cases), array_keys($actual[$class]), $class);

			foreach ($cases as $name => $pin) {
				self::assertSame($pin, $actual[$class][$name], sprintf('%s: %s', $class, $name));

				$checked++;
				$refused += isset($pin['refused']) ? 1 : 0;
			}
		}

		// Neither everything accepted nor everything refused: the comparison tells the two apart
		self::assertGreaterThan(0, $refused);
		self::assertGreaterThan($refused, $checked);
	}

	/**
	 * @return array<string, array<string, array<string, mixed>>>
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private static function decode(string $json): array
	{
		$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($decoded) || isset($decoded['error'])) {
			throw new RuntimeException(sprintf('The child process did not report states: %s', $json));
		}

		/** @var array<string, array<string, array<string, mixed>>> $decoded */
		return $decoded;
	}

	/**
	 * @throws RuntimeException
	 */
	private static function execute(string $option = ''): string
	{
		$command = sprintf(
			'FB_APP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s%s 2>&1',
			escapeshellarg(__DIR__ . '/../../..'),
			escapeshellarg('state-roots-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-state-roots.php'),
			$option !== '' ? ' ' . $option : '',
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		return $output;
	}

}
