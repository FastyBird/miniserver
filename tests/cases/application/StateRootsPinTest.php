<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_key_exists;
use function array_keys;
use function count;
use function escapeshellarg;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function preg_replace;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
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

	private const string PROPERTY = 'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\States\\Property';

	private const string ACTION = 'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\States\\Action';

	private const string CONDITION = 'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\States\\Condition';

	/**
	 * What the property state and the trigger action say when they refuse an input (Valinor writes one line per refused
	 * value, the path of the value and what was wrong with it): the path and a part of the message
	 */
	private const array MESSAGES = [
		self::PROPERTY => [
			'no id' => ['id', 'Cannot be empty and must be filled'],
			'an id which is not a UUID' => ['id', 'is not a valid UUID'],
			'an id of a UUID without dashes' => ['id', 'is not a valid UUID'],
			'an id which is a number' => ['id', 'is not a valid string'],
			'actual_value is a list' => ['actual_value', 'does not match'],
			'actual_value is an empty list' => ['actual_value.value', 'Cannot be empty and must be filled'],
			'actual_value is a map' => ['actual_value', 'does not match'],
			'expected_value is a list' => ['expected_value', 'does not match'],
			'expected_value is an empty list' => ['expected_value.value', 'Cannot be empty and must be filled'],
			'expected_value is a map' => ['expected_value', 'does not match'],
			'pending is a date text in another format' => ['pending', 'does not match any of the following formats'],
			'pending is an empty text' => ['pending', 'does not match'],
			'pending is a text' => ['pending', 'does not match any of the following formats'],
			'pending is 1' => ['pending', 'does not match any of the following formats'],
			'pending is 0' => ['pending', 'does not match any of the following formats'],
			'pending is null' => ['pending', 'Cannot be empty and must be filled'],
			'pending is a list' => ['pending', 'does not match'],
			'pending is a timestamp' => ['pending', 'does not match any of the following formats'],
			'valid as "yes"' => ['valid', 'is not a valid boolean'],
			'valid as "no"' => ['valid', 'is not a valid boolean'],
			'valid as "on"' => ['valid', 'is not a valid boolean'],
			'valid as "off"' => ['valid', 'is not a valid boolean'],
			'valid as "01"' => ['valid', 'is not a valid boolean'],
			'valid as " 1"' => ['valid', 'is not a valid boolean'],
			'valid as "1 "' => ['valid', 'is not a valid boolean'],
			'valid as "-1"' => ['valid', 'is not a valid boolean'],
			'valid as "2"' => ['valid', 'is not a valid boolean'],
			'valid as 2' => ['valid', 'is not a valid boolean'],
			'valid as -1' => ['valid', 'is not a valid boolean'],
			'valid as 1.0' => ['valid', 'is not a valid boolean'],
			'valid as 0.0' => ['valid', 'is not a valid boolean'],
			'valid as ""' => ['valid', 'is not a valid boolean'],
			'valid as null' => ['valid', 'is not a valid boolean'],
			'valid as a list' => ['valid', 'is not a valid boolean'],
			'created_at is fractions of a second' => ['created_at', 'does not match any of the following formats'],
			'updated_at is fractions of a second' => ['updated_at', 'does not match any of the following formats'],
			'created_at is another format' => ['created_at', 'does not match any of the following formats'],
			'updated_at is another format' => ['updated_at', 'does not match any of the following formats'],
			'created_at is a date without a time' => ['created_at', 'does not match any of the following formats'],
			'updated_at is a date without a time' => ['updated_at', 'does not match any of the following formats'],
			'created_at is a timestamp' => ['created_at', 'does not match any of the following formats'],
			'updated_at is a timestamp' => ['updated_at', 'does not match any of the following formats'],
			'created_at is false' => ['created_at', 'does not match'],
			'updated_at is false' => ['updated_at', 'does not match'],
			'created_at is a list' => ['created_at', 'does not match'],
			'updated_at is a list' => ['updated_at', 'does not match'],
		],
		self::ACTION => [
			'no id' => ['id', 'Cannot be empty and must be filled'],
			'an id which is not a UUID' => ['id', 'is not a valid UUID'],
			'validation_result as "yes"' => ['validation_result', 'is not a valid boolean'],
			'validation_result as "01"' => ['validation_result', 'is not a valid boolean'],
			'validation_result as "2"' => ['validation_result', 'is not a valid boolean'],
			'validation_result as 2' => ['validation_result', 'is not a valid boolean'],
			'validation_result as 1.0' => ['validation_result', 'is not a valid boolean'],
			'validation_result as ""' => ['validation_result', 'is not a valid boolean'],
			'validation_result as null' => ['validation_result', 'is not a valid boolean'],
			'validation_result as a list' => ['validation_result', 'is not a valid boolean'],
			'created_at is another format' => ['created_at', 'does not match any of the following formats'],
			'created_at is a timestamp' => ['created_at', 'does not match any of the following formats'],
			'created_at is a list' => ['created_at', 'does not match'],
			'updated_at is another format' => ['updated_at', 'does not match any of the following formats'],
			'updated_at is a timestamp' => ['updated_at', 'does not match any of the following formats'],
			'updated_at is a list' => ['updated_at', 'does not match'],
		],
	];

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
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testARefusalNamesTheValueAndWhatWasWrongWithIt(): void
	{
		$actual = self::decode(self::execute('--messages'));
		$wrong = [];

		foreach (self::MESSAGES as $class => $messages) {
			$refused = [];

			foreach ($actual[$class] as $name => $result) {
				if (isset($result['refused'])) {
					$refused[] = $name;
				}
			}

			self::assertSame(array_keys($messages), $refused, $class);

			foreach ($messages as $name => [$path, $kind]) {
				$message = $actual[$class][$name]['message'] ?? null;

				self::assertIsString($message, $name);

				$first = (string) preg_replace('/^Could not map data to state: /', '', $message);

				if (!str_starts_with($first, $path . ': ') || !str_contains($first, $kind)) {
					$wrong[] = sprintf('%s: %s: "%s" is not "%s: ... %s"', $class, $name, $first, $path, $kind);
				}
			}
		}

		self::assertSame([], $wrong);
	}

	/**
	 * The side of Mapper::map()'s dispatch every state is on, now that the state roots moved: no state of the RedisDb
	 * plugin's tree, of the Devices module's property states or of the CouchDb plugin's tree, in any package, is
	 * mapped by orisai. The counts are of every class and interface of a tree, the root included; a state added to a
	 * tree changes them, and the author says so here
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testNoStateReachesMappedObject(): void
	{
		$census = json_decode(self::execute('--census'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($census);

		foreach (['redisdb' => 7, 'devices' => 8, 'couchdb' => 1] as $family => $count) {
			$classes = $census[$family] ?? null;

			self::assertIsArray($classes, $family);
			self::assertCount($count, $classes, $family);

			foreach ($classes as $class => $mapped) {
				self::assertFalse($mapped, sprintf('%s (%s) is mapped by orisai', $class, $family));
			}
		}
	}

	/**
	 * What an object where a value is expected makes. orisai took any object for the value of a property state, for its
	 * pending flag and its dates (ObjectValue) and then failed with a TypeError, a defect and not a refusal, for every
	 * object that was not a date or a payload case; Valinor takes the dates, mutable or not, and the payload cases, and
	 * refuses the rest. The trigger states refused a date object and take it now, as the documents do
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testOnlyTheObjectsOfTheTypesAreTaken(): void
	{
		$report = json_decode(self::execute('--objects'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($report);

		foreach ([self::PROPERTY => 31, self::ACTION => 10, self::CONDITION => 10] as $class => $count) {
			$entries = $report[$class] ?? null;

			self::assertIsArray($entries, $class);
			self::assertCount($count, $entries, $class);

			foreach ($entries as $name => $result) {
				self::assertIsArray($result);
				self::assertArrayNotHasKey('error', $result, sprintf('%s: %s', $class, $name));
				self::assertSame(
					str_contains($name, 'DateTime') || str_contains($name, ' a Button') || str_contains(
						$name,
						' a Switcher',
					) || str_contains(
						$name,
						' a Cover',
					),
					array_key_exists('json', $result),
					sprintf('%s: %s', $class, $name),
				);
			}
		}
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
