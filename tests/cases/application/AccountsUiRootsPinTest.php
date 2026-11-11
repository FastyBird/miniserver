<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_keys;
use function count;
use function escapeshellarg;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function preg_replace;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * What the documents of the Accounts module and of the Ui module's Document interface map to, pinned (#724 PR4f): the
 * account, its e-mail address, identity and role, the dashboards, tabs and groups, the widgets with their displays and
 * data sources, the action of a data source, and the data sources of the DevicesModuleUiModule bridge. The production
 * container is booted in a child process (bootstrap-accounts-ui-roots.php) and every concrete document is given the
 * same inputs through DocumentFactory: each of its fields in every wrong form there is, next to the whole document.
 * What each made, the JSON the exchange and the API encode and serialize() of the document, or that it was refused, has
 * to be what fixtures/accounts-ui-roots.json holds: the output of the same script when orisai mapped them, on d7e28415c.
 * The differences there are, and only these are, the ones CHANGED names
 */
final class AccountsUiRootsPinTest extends TestCase
{

	private const string ACCOUNT = 'FastyBird\\Module\\Accounts\\Documents\\Accounts\\Account';

	private const string EMAIL = 'FastyBird\\Module\\Accounts\\Documents\\Emails\\Email';

	private const string ROLE = 'FastyBird\\Module\\Accounts\\Documents\\Roles\\Role';

	private const string DASHBOARD = 'FastyBird\\Module\\Ui\\Documents\\Dashboards\\Dashboard';

	private const string SLIDER = 'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\Slider';

	private const string CHART = 'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\ChartGraph';

	private const string CHANNEL = 'FastyBird\\Bridge\\DevicesModuleUiModule\\Documents\\Widgets\\DataSources\\ChannelProperty';

	/**
	 * The inputs whose answer is not the one orisai gave, both of them a defect of orisai's:
	 *
	 * - a text with a NUL byte as the value of a data source: the date rule of the union threw a ValueError from
	 *   createFromFormat(), which Mapper turned into a refusal of the whole document (#836); the text is a text, as for the
	 *   state of a property
	 * - `enable_min_max: null` for a chart: the rule took null and the property is a bool, so the setter raised a TypeError;
	 *   the input is refused
	 */
	private const array CHANGED = [
		self::CHANNEL => ['value: a date with a NUL byte'],
		self::CHART => ['enable_min_max: null'],
	];

	/**
	 * What a document says when it refuses an input (Valinor writes one line per refused value, the path of the value and
	 * what was wrong with it): the path and a part of the message, for a few kinds of wrong value
	 */
	private const array MESSAGES = [
		self::ACCOUNT => [
			'id: a short text' => ['id', 'is not a valid UUID'],
			'first_name: an empty string' => ['first_name', 'must not be blank'],
			'first_name: one' => ['first_name', 'is not a valid string'],
			'state: an upper case word' => ['state', "does not match any of 'active'"],
			'email: an address without a domain' => ['email', 'does not match the pattern'],
			'registered: a date in another format' => ['registered', 'does not match any of the following formats'],
			'children: one UUID as a text' => ['children', 'does not match'],
			'parent: a short text' => ['parent', 'is not a valid UUID'],
			'nothing' => ['id', 'Cannot be empty and must be filled'],
		],
		self::EMAIL => [
			'address: an address with spaces' => ['address', 'does not match the pattern'],
			'default: the text yes' => ['default', 'is not a valid boolean'],
			'default: the int 2' => ['default', 'is not a valid boolean'],
			'account: missing' => ['account', 'Cannot be empty and must be filled'],
		],
		self::ROLE => [
			'anonymous: the text 01' => ['anonymous', 'is not a valid boolean'],
			'name: spaces' => ['name', 'must not be blank'],
		],
		self::DASHBOARD => [
			'priority: an int as a text' => ['priority', 'is not a valid integer'],
			'created_at: an empty string' => ['created_at', 'does not match'],
			'created_at: a date in another format' => ['created_at', 'does not match any of the following formats'],
			'tabs: a list in a list' => ['tabs.0', 'is not a valid string'],
		],
		self::SLIDER => [
			'precision: a negative precision' => ['precision', 'does not match any of `int<0, max>`, `null`'],
			'minimum_value: a float as a text' => ['minimum_value', 'does not match any of `float`, `null`'],
			'precision: missing' => ['precision', 'Cannot be empty and must be filled'],
		],
		self::CHART => [
			'enable_min_max: the text yes' => ['enable_min_max', 'is not a valid boolean'],
		],
	];

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryDocumentIsWhatOrisaiMade(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/accounts-ui-roots.json'));
		$actual = self::decode(self::execute(false));

		// The classes first: a document that went missing, or a new one, is not a difference of its inputs
		$expected = array_keys($pinned);
		$found = array_keys($actual);

		sort($expected);
		sort($found);

		self::assertSame($expected, $found);
		self::assertCount(23, $found);

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
	 * The two inputs which are not what orisai made of them, stated outright
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheTwoChangedInputsAreWhatTheCommentsOfChangedSay(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/accounts-ui-roots.json'));
		$actual = self::decode(self::execute(false));

		self::assertSame(['refused' => true], $pinned[self::CHANNEL]['value: a date with a NUL byte']);
		self::assertSame(['error' => 'TypeError'], $pinned[self::CHART]['enable_min_max: null']);

		$text = $actual[self::CHANNEL]['value: a date with a NUL byte']['json'] ?? null;

		self::assertIsString($text);
		self::assertStringContainsString("\"value\":\"2020-04-01T12:00:00+00:00\\u0000\"", $text);
		self::assertSame(['refused' => true], $actual[self::CHART]['enable_min_max: null']);
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

				$first = (string) preg_replace('/^Could not map data to document: /', '', $message);

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

				$message = $result['message'] ?? '';
				$first = (string) preg_replace(
					'/^Could not map data to document: /',
					'',
					is_string($message) ? $message : '',
				);

				// Only a refusal of the mapping names a path: the factory's own (the discriminator) and what toArray() throws do not
				if (
					!is_string($message)
					|| !str_starts_with($message, 'Could not map data to document: ')
					|| str_starts_with($first, '*root*')
				) {
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
	public function testNoDocumentOfTheAccountsAndUiTreesReachesMappedObject(): void
	{
		$census = json_decode(self::execute(false, '--census'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($census);

		$counts = [
			'account' => 1,
			'email' => 1,
			'identity' => 1,
			'role' => 1,
			'ui' => 23,
			'widget' => 5,
			'dataSource' => 6,
			'display' => 8,
		];

		foreach ($counts as $family => $count) {
			self::assertIsArray($census[$family] ?? null, $family);
			self::assertCount($count, $census[$family], $family);

			foreach ($census[$family] as $class => $mapped) {
				self::assertFalse($mapped, sprintf('%s (%s) is mapped by orisai', $class, $family));
			}
		}
	}

	/**
	 * What the documents make of an object where their scalar or array form is expected. orisai refused all of these and
	 * Valinor takes the object of the type it was given (a date, the enum of an action) and a traversable for a list of
	 * UUIDs, a generator excepted. Nothing in the application builds a document from such a value; this documents what happens if
	 * something does
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testAnObjectIsTakenWhereOrisaiRefusedIt(): void
	{
		$report = json_decode(self::execute(false, '--objects'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($report);
		self::assertCount(5, $report);

		$taken = 0;
		$refused = 0;
		$counts = [];

		foreach ($report as $class => $cases) {
			self::assertIsArray($cases, (string) $class);

			$counts[] = count($cases);

			foreach ($cases as $name => $result) {
				self::assertIsArray($result);

				if (isset($result['json'])) {
					$taken++;
				} else {
					self::assertArrayHasKey('refused', $result, sprintf('%s: %s', $class, $name));
					self::assertContains($name, [
						'registered as an object of no date',
						'children as a Generator',
						'value as an object of no value',
					]);

					$refused++;
				}
			}
		}

		sort($counts);

		self::assertSame(10, $taken);
		self::assertSame(3, $refused);
		// A slider's date, the action's enum, a dashboard's two dates and a list, a data source's two values, an account's six
		self::assertSame([1, 1, 2, 3, 6], $counts);
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
			escapeshellarg('accounts-ui-roots-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-accounts-ui-roots.php'),
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
