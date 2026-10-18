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
use function preg_replace;
use function shell_exec;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * What the documents that extend the Devices module's Connector and Device documents map to, pinned (#724 PR4). The
 * production container is booted in a child process (bootstrap-document-roots.php) and every concrete document of
 * every package that extends one of the two roots is given the same inputs through DocumentFactory. What each made, the
 * JSON the exchange and the API encode and serialize() of the document, or that it was refused, has to be what
 * fixtures/document-roots.json holds: the output of the same script on 45787d7b4, where orisai still mapped all of them.
 * The documents of the connectors, the bridges and the addon have no mapping of their own, so they are held to the pins
 * of the root they extend, one by one
 */
final class DocumentRootsPinTest extends TestCase
{

	private const string CONNECTOR_GENERIC = 'FastyBird\\Module\\Devices\\Documents\\Connectors\\Generic';

	private const string DEVICE_GENERIC = 'FastyBird\\Module\\Devices\\Documents\\Devices\\Generic';

	/**
	 * What the generic document of each root says when it refuses an input (Valinor writes one line per refused value,
	 * the path of the value and what was wrong with it): the path and a part of the message
	 */
	private const array MESSAGES = [
		self::CONNECTOR_GENERIC => [
			'a date with fractions of a second' => ['created_at', 'does not match any of the following formats'],
			'a date without a time' => ['updated_at', 'does not match any of the following formats'],
			'a date as a timestamp' => ['created_at', 'does not match any of the following formats'],
			'an empty date' => ['created_at', 'does not match'],
			'enabled as an int' => ['enabled', 'is not a valid boolean'],
			'enabled as a text' => ['enabled', 'is not a valid boolean'],
			'enabled as null' => ['enabled', 'is not a valid boolean'],
			'no id' => ['id', 'Cannot be empty and must be filled'],
			'an id which is not a UUID' => ['id', 'is not a valid UUID'],
			'an id of a UUID without dashes' => ['id', 'is not a valid UUID'],
			'no category' => ['category', 'Cannot be empty and must be filled'],
			'an unknown category' => ['category', 'does not match'],
			'a category which is a number' => ['category', 'does not match'],
			'no identifier' => ['identifier', 'is not a valid string'],
			'a blank identifier' => ['identifier', 'must not be blank'],
			'an empty identifier' => ['identifier', 'must not be blank'],
			'an identifier which is a number' => ['identifier', 'is not a valid string'],
			'a name which is a number' => ['name', 'does not match'],
			'a comment which is a list' => ['comment', 'does not match'],
			'an owner which is not a UUID' => ['owner', 'is not a valid UUID'],
			'a property which is not a UUID' => ['properties.0', 'is not a valid UUID'],
			'properties which are a text' => ['properties', 'does not match'],
			'a control which is a number' => ['controls.0', 'is not a valid string'],
			'a device which is null' => ['devices.0', 'is not a valid string'],
		],
		self::DEVICE_GENERIC => [
			'a date with fractions of a second' => ['created_at', 'does not match any of the following formats'],
			'a date as a timestamp' => ['created_at', 'does not match any of the following formats'],
			'an empty date' => ['updated_at', 'does not match'],
			'no id' => ['id', 'Cannot be empty and must be filled'],
			'an id which is not a UUID' => ['id', 'is not a valid UUID'],
			'no connector' => ['connector', 'Cannot be empty and must be filled'],
			'a connector which is not a UUID' => ['connector', 'is not a valid UUID'],
			'a connector which is null' => ['connector', 'is not a valid string'],
			'no category' => ['category', 'Cannot be empty and must be filled'],
			'an unknown category' => ['category', 'does not match'],
			'no identifier' => ['identifier', 'is not a valid string'],
			'a blank identifier' => ['identifier', 'must not be blank'],
			'an identifier which is a number' => ['identifier', 'is not a valid string'],
			'a name which is a number' => ['name', 'does not match'],
			'an owner which is not a UUID' => ['owner', 'is not a valid UUID'],
			'a parent which is not a UUID' => ['parents.0', 'is not a valid UUID'],
			'children which are a text' => ['children', 'does not match'],
			'a channel which is a number' => ['channels.0', 'is not a valid string'],
			'a date in another format' => ['created_at', 'does not match any of the following formats'],
		],
	];

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryDocumentIsWhatOrisaiMade(): void
	{
		$pinned = self::decode((string) file_get_contents(__DIR__ . '/fixtures/document-roots.json'));
		$actual = self::decode(self::execute(false));

		// The classes first: a document that went missing, or a new one, is not a difference of its inputs
		$expected = array_keys($pinned);
		$found = array_keys($actual);

		sort($expected);
		sort($found);

		self::assertSame($expected, $found);
		self::assertGreaterThanOrEqual(28, count($found));

		$checked = 0;
		$refused = 0;

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
		$actual = self::decode(self::execute(true));
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

				$first = (string) preg_replace('/^Could not map data to document: /', '', $message);

				if (!str_starts_with($first, $path . ': ') || !str_contains($first, $kind)) {
					$wrong[] = sprintf('%s: %s: "%s" is not "%s: ... %s"', $class, $name, $first, $path, $kind);
				}
			}
		}

		self::assertSame([], $wrong);
	}

	/**
	 * Which side of Mapper::map() each tree is on: the Connector and Device roots, and every document beneath them in
	 * every package, are Valinor's, the Channel root and every document beneath it stay orisai's until its own pull
	 * request. A tree that is split between the two would map its documents with rules that are not theirs
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryTreeIsOnTheSideOfItsRoot(): void
	{
		$census = json_decode(self::execute(false, '--census'), true, 512, JSON_THROW_ON_ERROR);

		self::assertIsArray($census);

		foreach (['connector' => [11, false], 'device' => [20, false], 'channel' => [54, true]] as $family => [$count, $orisai]) {
			self::assertIsArray($census[$family] ?? null, $family);
			self::assertGreaterThanOrEqual($count, count($census[$family]), $family);

			foreach ($census[$family] as $class => $mapped) {
				self::assertSame($orisai, $mapped, sprintf('%s (%s)', $class, $family));
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
			escapeshellarg('document-roots-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-document-roots.php'),
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
