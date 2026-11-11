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
 * What the documents of the Triggers module's six trees map to, pinned (#724 PR4e): Trigger, Notification, Action,
 * Condition, Control and the Action of a control, and the documents of the automators that extend them. The production
 * container is booted in a child process (bootstrap-trigger-roots.php) and every concrete document is given the same
 * inputs through DocumentFactory: each of its fields in every wrong form there is, next to the whole document. What each
 * made, the JSON the exchange and the API encode and serialize() of the document, or that it was refused, has to be
 * what fixtures/trigger-roots.json holds: the output of the same script when orisai mapped them, on ff789635c. The
 * differences there are, and only these are, the ones CHANGED names and the tests below state
 */
final class TriggerRootsPinTest extends TestCase
{

	private const string CONTROL = 'FastyBird\\Module\\Triggers\\Documents\\Triggers\\Controls\\Control';

	private const string CHANNEL_ACTION = 'FastyBird\\Automator\\DevicesModule\\Documents\\Actions\\ChannelPropertyAction';

	private const string DEVICE_ACTION = 'FastyBird\\Automator\\DevicesModule\\Documents\\Actions\\DevicePropertyAction';

	private const string TIME = 'FastyBird\\Automator\\DateTime\\Documents\\Conditions\\TimeCondition';

	private const string EMAIL = 'FastyBird\\Module\\Triggers\\Documents\\Notifications\\Email';

	private const string AUTOMATIC = 'FastyBird\\Module\\Triggers\\Documents\\Triggers\\Automatic';

	/**
	 * The inputs whose answer is not the one orisai gave. The Action of a control and the two property actions of the
	 * DevicesModule automator are not in it by name: see the tests below
	 *
	 * - the owner of a control: its rule was a UUID and not a UUID or null, so a control with `owner: null`, which is what
	 *   the entity makes of a control with no owner, was refused for it; every other document takes null
	 */
	private const array CHANGED = [
		self::CONTROL => ['owner: null'],
	];

	/**
	 * The two property actions declared the value a BoolValue and held it in a string: every input was refused, or ended
	 * in a TypeError for a bool, which is the TypeError orisai's setter of the property raises. The pins hold no input of
	 * these classes that was accepted, so they hold nothing to compare an accepted one with
	 */
	private const array NEVER_BUILT = [self::CHANNEL_ACTION, self::DEVICE_ACTION];

	/**
	 * What a document says when it refuses an input (Valinor writes one line per refused value, the path of the value and
	 * what was wrong with it): the path and a part of the message, for a few kinds of wrong value
	 */
	private const array MESSAGES = [
		self::AUTOMATIC => [
			'id: missing' => ['id', 'Cannot be empty and must be filled'],
			'id: a text' => ['id', 'is not a valid UUID'],
			'id: a UUID without dashes' => ['id', 'is not a valid UUID'],
			'name: an empty string' => ['name', 'must not be blank'],
			'name: spaces' => ['name', 'must not be blank'],
			'name: one' => ['name', 'is not a valid string'],
			'enabled: a numeric text' => ['enabled', 'is not a valid boolean'],
			'is_triggered: a text' => ['is_triggered', 'does not match any of `bool`, `null`'],
			'owner: a text' => ['owner', 'is not a valid UUID'],
			'comment: a list' => ['comment', 'does not match'],
		],
		self::EMAIL => [
			'email: missing' => ['email', 'is not a valid string'],
			'email: an address without a domain' => ['email', 'does not match the pattern'],
			'email: an address with spaces' => ['email', 'does not match the pattern'],
			'email: an empty string' => ['email', 'does not match the pattern'],
		],
		self::TIME => [
			'time: a date in another format' => ['time', 'does not match any of the following formats'],
			'days: an empty list' => ['days', 'Cannot be empty and must be filled'],
			'days: a day out of the week' => ['days.0', 'is not a valid integer between 1 and 7'],
			'days: eight days' => ['*root*', 'at most 7 days'],
			'days: days as texts' => ['days.0', 'is not a valid integer between 1 and 7'],
		],
	];

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryDocumentIsWhatOrisaiMade(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/trigger-roots.json'));
		$actual = self::decode(self::execute(false));

		// The classes first: a document that went missing, or a new one, is not a difference of its inputs
		$expected = array_keys($pinned);
		$found = array_keys($actual);

		sort($expected);
		sort($found);

		self::assertSame($expected, $found);
		self::assertCount(12, $found);

		$checked = 0;
		$refused = 0;

		foreach ($pinned as $class => $cases) {
			self::assertSame(array_keys($cases), array_keys($actual[$class]), $class);

			foreach ($cases as $name => $pin) {
				if (in_array($class, self::NEVER_BUILT, true)) {
					self::assertTrue(isset($pin['refused']) || isset($pin['error']), sprintf('%s: %s', $class, $name));

					continue;
				}

				if (in_array($name, self::CHANGED[$class] ?? [], true)) {
					self::assertNotSame($pin, $actual[$class][$name], sprintf('%s: %s is no longer changed', $class, $name));

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
	 * The two property actions of the DevicesModule automator, which orisai could not build from anything: they take the
	 * value as the text it is, as the entity holds it, and refuse what the other documents of their trees refuse
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testThePropertyActionsAreBuiltFromTheTextOfTheirEntity(): void
	{
		$actual = self::decode(self::execute(false));

		foreach ([self::CHANNEL_ACTION, self::DEVICE_ACTION] as $class) {
			$stored = $actual[$class]['the fields of a stored one'];

			self::assertArrayHasKey('json', $stored, $class);
			self::assertStringContainsString('"value":"on"', $stored['json']);

			foreach (
				[
					'id: a text',
					'trigger: a text',
					'enabled: one',
					'enabled: a numeric text',
					'is_triggered: a text',
					'owner: a text',
					'device: a text',
					'property: a text',
					'value: one',
					'value: true',
					'value: a list',
					'value: missing',
				] as $name
			) {
				self::assertArrayHasKey('refused', $actual[$class][$name], sprintf('%s: %s', $class, $name));
			}

			// The same answer as the condition on the same property for what the two share
			self::assertArrayHasKey('json', $actual[$class]['owner: null'], $class);
			self::assertArrayHasKey('json', $actual[$class]['is_triggered: an empty string'], $class);
			self::assertArrayHasKey('json', $actual[$class]['id: an upper case UUID'], $class);
		}
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
				$first = (string) preg_replace('/^Could not map data to document: /', '', is_string($message) ? $message : '');

				// Only a refusal of the mapping names a path: the factory's own (the discriminator) and what toArray() throws do not
				if (!is_string($message) || !str_starts_with($message, 'Could not map data to document: ') || str_starts_with($first, '*root*')) {
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
	 * The side of Mapper::map()'s dispatch each tree is on, now that the roots moved: no document of the six trees, in
	 * any package, is mapped by orisai. The counts are of every class of a tree, the abstract ones and the root included
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testNoDocumentOfTheSixTreesReachesMappedObject(): void
	{
		$census = json_decode(self::execute(false, '--census'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($census);

		foreach (
			['trigger' => 3, 'notification' => 3, 'action' => 3, 'condition' => 5, 'control' => 1, 'controlAction' => 1] as $family => $count
		) {
			self::assertIsArray($census[$family] ?? null, $family);
			self::assertCount($count, $census[$family], $family);

			foreach ($census[$family] as $class => $mapped) {
				self::assertFalse($mapped, sprintf('%s (%s) is mapped by orisai', $class, $family));
			}
		}
	}

	/**
	 * What the documents make of an object where their scalar or array form is expected. orisai refused all of these and
	 * Valinor takes the object of the type it was given (a date, the enum of an action) and any traversable for the days of
	 * a condition. Nothing in the application builds a document from such a value; this documents what happens if
	 * something does
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testAnObjectIsTakenWhereOrisaiRefusedIt(): void
	{
		$report = json_decode(self::execute(false, '--objects'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($report);
		self::assertCount(3, $report);

		$taken = 0;
		$refused = 0;

		foreach ($report as $class => $cases) {
			self::assertIsArray($cases, (string) $class);

			foreach ($cases as $name => $result) {
				self::assertIsArray($result);

				if (isset($result['json'])) {
					$taken++;
				} else {
					self::assertArrayHasKey('refused', $result, sprintf('%s: %s', $class, $name));
					self::assertSame('date as an object of no date', $name);

					$refused++;
				}
			}
		}

		self::assertSame(8, $taken);
		self::assertSame(1, $refused);
		self::assertSame(
			[1, 3, 5],
			[
				count($report['FastyBird\\Module\\Triggers\\Documents\\Triggers\\Controls\\Action'] ?? []),
				count($report['FastyBird\\Automator\\DateTime\\Documents\\Conditions\\DateCondition'] ?? []),
				count($report['FastyBird\\Automator\\DateTime\\Documents\\Conditions\\TimeCondition'] ?? []),
			],
		);
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
			escapeshellarg('trigger-roots-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-trigger-roots.php'),
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
