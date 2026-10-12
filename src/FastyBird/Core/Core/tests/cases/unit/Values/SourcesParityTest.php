<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Values;

use BackedEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_keys;
use function basename;
use function file_get_contents;
use function glob;
use function is_a;
use function preg_match_all;
use function sort;
use const PREG_SET_ORDER;

/**
 * PHP and TypeScript each declare the source identifiers (`com.fastybird.…`) every message,
 * document and JSON:API type carries. The two lists were once edited by hand, separately, and
 * drifted (#750). PHP is the truth; this test parses `assets/values/types/source.ts` and requires
 * every `Values\Types\Sources\*` enum to have a TS twin with the same case names and values.
 */
final class SourcesParityTest extends TestCase
{

	private const string TS_FILE = __DIR__ . '/../../../../assets/values/types/source.ts';

	private const string PHP_DIRECTORY = __DIR__ . '/../../../../src/Values/Types/Sources';

	/**
	 * PHP case names whose TS twin is named differently. `Plugin::WS_SERVER` is `WS_EXCHANGE` in TS
	 * (the value is the same); #744 replaces both, and this entry goes with it.
	 */
	private const array TS_NAME_ALIASES = [
		'PluginSource' => ['WS_EXCHANGE' => 'WS_SERVER'],
	];

	public function testEveryPhpEnumHasATsTwinAndNothingMore(): void
	{
		$php = [];

		foreach (self::phpEnumNames() as $name) {
			$php[] = $name . 'Source';
		}

		$ts = array_keys($this->tsEnums());

		sort($php);
		sort($ts);

		self::assertSame($php, $ts);
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public static function enumProvider(): array
	{
		$provided = [];

		foreach (self::phpEnumNames() as $name) {
			$provided[$name] = [$name];
		}

		return $provided;
	}

	#[DataProvider('enumProvider')]
	public function testCasesMatch(string $name): void
	{
		$class = 'FastyBird\\Core\\Values\\Types\\Sources\\' . $name;

		self::assertTrue(is_a($class, BackedEnum::class, true), $class . ' is not a backed enum');

		$expected = [];

		foreach ($class::cases() as $case) {
			$expected[$case->name] = $case->value;
		}

		$tsEnums = $this->tsEnums();

		self::assertArrayHasKey($name . 'Source', $tsEnums);

		$actual = [];

		foreach ($tsEnums[$name . 'Source'] as $caseName => $value) {
			$actual[self::TS_NAME_ALIASES[$name . 'Source'][$caseName] ?? $caseName] = $value;
		}

		self::assertEquals($expected, $actual, 'source.ts ' . $name . 'Source differs from Sources\\' . $name);
	}

	/**
	 * @return array<int, string>
	 */
	private static function phpEnumNames(): array
	{
		$names = [];

		$files = glob(self::PHP_DIRECTORY . '/*.php');

		self::assertIsArray($files);

		foreach ($files as $file) {
			$name = basename($file, '.php');

			// `Source` is the marker interface, not an enum
			if ($name !== 'Source') {
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private function tsEnums(): array
	{
		$content = file_get_contents(self::TS_FILE);

		self::assertIsString($content);

		$found = preg_match_all('/export enum (\w+) \{(.*?)\n\}/s', $content, $enums, PREG_SET_ORDER);

		self::assertGreaterThan(0, $found);

		$result = [];

		foreach ($enums as $enum) {
			$cases = [];

			preg_match_all('/^\s*(\w+) = \'([^\']*)\',?$/m', $enum[2], $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$cases[$match[1]] = $match[2];
			}

			$result[$enum[1]] = $cases;
		}

		return $result;
	}

}
