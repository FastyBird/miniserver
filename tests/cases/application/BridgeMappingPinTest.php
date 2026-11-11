<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_keys;
use function escapeshellarg;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * What the mapping classes of the two HomeKit bridges that read a mapping file of their connector map to, pinned (#724
 * PR5): the mapping of Shelly generation 1 and 2 and of Viera, their accessories, services and characteristics. The
 * production container is booted in a child process (bootstrap-bridge-mapping.php) and every class is given the same
 * inputs through Core's Mapper: the mapping file as it is, and a sample taken from it with its fields one at a time in
 * every wrong form there is. What each made, serialize() of the object or that it was refused, has to be what
 * fixtures/bridge-mapping.json holds: the output of the same script when orisai mapped them, on d7e28415c. The differences
 * there are, and only these are, the ones CHANGED names
 */
final class BridgeMappingPinTest extends TestCase
{

	private const string SHELLY = 'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\';

	private const string VIERA = 'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\';

	private const string LIGHT = self::SHELLY . 'Accessories\\LightAccessory';

	private const string LIGHTBULB = self::SHELLY . 'Services\\LightbulbService';

	private const string CHARACTERISTIC = self::SHELLY . 'Characteristics\\BasicCharacteristic';

	private const string GEN1 = self::SHELLY . 'Gen1';

	private const string GEN2 = self::SHELLY . 'Gen2';

	private const string SERVICES = self::VIERA . 'Services';

	/**
	 * The inputs whose answer is not the one orisai gave:
	 *
	 * - a mapping of one field, read from the value of the field as well as from an array that holds it (Valinor reads a
	 *   class of a single parameter from its value too): without the `accessories` or `services` key it is the empty list,
	 *   where orisai refused it. The connectors always give the key
	 * - the first service of a Shelly accessory, which reads `index_start` as an int: a float, which orisai let through
	 *   and then failed on with a TypeError of the property, is refused unless it has no decimals, a text with spaces around
	 *   a number is read as the number, and a text of a number that no int holds is refused where orisai cut it to the
	 *   greatest int
	 */
	private const array CHANGED = [
		self::GEN1 => ['nothing', 'accessories: missing'],
		self::GEN2 => ['nothing', 'accessories: missing'],
		self::SERVICES => ['nothing', 'services: missing'],
		self::LIGHTBULB => [
			'index_start: a float',
			'index_start: a numeric text with spaces',
			'index_start: a text of a float',
			'index_start: a text of a float with no decimals',
			'index_start: a float with no decimals',
			'index_start: a text of a big number',
		],
	];

	/**
	 * What a mapping class says when it refuses an input (Valinor writes one line per refused value, the path of the value
	 * and what was wrong with it): the path and a part of the message, for a few kinds of wrong value
	 */
	private const array MESSAGES = [
		self::LIGHT => [
			'type: missing' => ['type', "does not match string value 'light'"],
			'models: a list with a blank' => ['models', 'must not hold a blank text'],
			'categories: a list with one that is no case' => ['categories.1', 'does not match any of'],
			'categories: a list with a negative key' => ['categories.-1', 'does not match type `int<0, max>`'],
			'services: an item of another kind' => ['services.0.type', "does not match string value 'Lightbulb'"],
		],
		self::LIGHTBULB => [
			'class: an empty string' => ['class', 'must not be blank'],
			'index_start: a text with a letter' => ['index_start', 'does not match any of `int`, `null`'],
			'multiple: the text yes' => ['multiple', 'is not a valid boolean'],
			'category: a word that is no case' => ['category', 'does not match any of'],
		],
		self::CHARACTERISTIC => [
			'format: a pair of three' => ['format', 'is not a valid format'],
			'property: a list with a blank' => ['property', 'must not hold a blank text'],
			'nullable: the text 01' => ['nullable', 'is not a valid boolean'],
			'type: a word that is no case' => ['type', 'does not match any of'],
		],
	];

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryDocumentIsWhatOrisaiMade(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/bridge-mapping.json'));
		$actual = self::decode(self::execute(false));

		// The classes first: a document that went missing, or a new one, is not a difference of its inputs
		$expected = array_keys($pinned);
		$found = array_keys($actual);

		sort($expected);
		sort($found);

		self::assertSame($expected, $found);
		self::assertCount(18, $found);

		$checked = 0;
		$refused = 0;

		foreach ($pinned as $class => $cases) {
			self::assertSame(array_keys($cases), array_keys($actual[$class]), $class);

			foreach ($cases as $name => $pin) {
				if (in_array($name, self::CHANGED[$class] ?? [], true)) {
					self::assertNotSame(
						$pin,
						$actual[$class][$name],
						sprintf('%s: %s is no longer changed', $class, $name),
					);

					continue;
				}

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
	 * The inputs which are not what orisai made of them, stated outright
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheChangedInputsAreWhatTheCommentsOfChangedSay(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/bridge-mapping.json'));
		$actual = self::decode(self::execute(false));

		foreach ([self::GEN1 => 'nothing', self::GEN2 => 'nothing', self::SERVICES => 'nothing'] as $class => $name) {
			self::assertSame(['refused' => true], $pinned[$class][$name], $class);
			self::assertStringContainsString(':0:{}', self::serializedOf($actual, $class, $name));
		}

		// orisai: a TypeError of the property for each of the floats
		foreach (
			['index_start: a float', 'index_start: a text of a float', 'index_start: a text of a float with no decimals'] as $name
		) {
			self::assertSame(['error' => 'TypeError'], $pinned[self::LIGHTBULB][$name], $name);
			self::assertSame(['refused' => true], $actual[self::LIGHTBULB][$name], $name);
		}

		self::assertStringContainsString(
			'indexStart";i:2;',
			self::serializedOf($actual, self::LIGHTBULB, 'index_start: a float with no decimals'),
		);
		self::assertStringContainsString(
			'indexStart";i:1;',
			self::serializedOf($actual, self::LIGHTBULB, 'index_start: a numeric text with spaces'),
		);
		self::assertSame(['refused' => true], $actual[self::LIGHTBULB]['index_start: a text of a big number']);
		self::assertSame(['refused' => true], $pinned[self::LIGHTBULB]['index_start: a numeric text with spaces']);
		self::assertStringContainsString(
			'indexStart";i:9223372036854775807;',
			self::serializedOf($pinned, self::LIGHTBULB, 'index_start: a text of a big number'),
		);
	}

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testARefusalNamesTheValueAndWhatWasWrongWithIt(): void
	{
		$actual = self::decode(self::execute(true));
		$wrong = [];

		foreach (self::MESSAGES as $class => $messages) {
			foreach ($messages as $name => [$path, $kind]) {
				$message = $actual[$class][$name]['message'] ?? null;

				self::assertIsString($message, $name);

				$first = $message;

				if (!str_starts_with($first, $path . ': ') || !str_contains($first, $kind)) {
					$wrong[] = sprintf('%s: %s: "%s" is not "%s: ... %s"', $class, $name, $first, $path, $kind);
				}
			}
		}

		// Every other refusal of a field starts with the path of the field the input was about, which the case names
		foreach ($actual as $class => $cases) {
			foreach ($cases as $name => $result) {
				if (!isset($result['refused']) || preg_match('/^(\w+): /', $name, $match) !== 1) {
					continue;
				}

				$first = $result['message'] ?? '';

				if (!is_string($first) || str_starts_with($first, '*root*')) {
					continue;
				}

				if (!str_starts_with($first, $match[1])) {
					$wrong[] = sprintf('%s: %s: "%s" is not about "%s"', $class, $name, $first, $match[1]);
				}
			}
		}

		self::assertSame([], $wrong);
	}

	/**
	 * The side of Mapper::map()'s dispatch each tree is on, now that the roots moved: no document of the Accounts module or
	 * of the Ui module's Document interface, in any package, is mapped by orisai. The counts are of every class of a tree, the abstract ones and the root included
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testNoMappingClassReachesMappedObject(): void
	{
		$census = json_decode(self::execute(false, '--census'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($census);

		$counts = ['shelly' => 15, 'viera' => 6];

		foreach ($counts as $family => $count) {
			self::assertIsArray($census[$family] ?? null, $family);
			self::assertCount($count, $census[$family], $family);

			foreach ($census[$family] as $class => $mapped) {
				self::assertFalse($mapped, sprintf('%s (%s) is mapped by orisai', $class, $family));
			}
		}
	}

	/**
	 * @param array<string, array<string, array<string, mixed>>> $results
	 */
	private static function serializedOf(array $results, string $class, string $name): string
	{
		$serialized = $results[$class][$name]['serialized'] ?? 'refused';

		return is_string($serialized) ? $serialized : 'refused';
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
			throw new RuntimeException(sprintf('The child process did not report documents: %s', $json));
		}

		/** @var array<string, array<string, array<string, mixed>>> $decoded */
		return $decoded;
	}

	/**
	 * @throws RuntimeException
	 */
	private static function execute(bool $withMessages, string $option = ''): string
	{
		$command = sprintf(
			'FB_APP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s%s%s 2>&1',
			escapeshellarg(__DIR__ . '/../../..'),
			escapeshellarg('bridge-mapping-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-bridge-mapping.php'),
			$withMessages ? ' --messages' : '',
			$option !== '' ? ' ' . $option : '',
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		return $output;
	}

}
