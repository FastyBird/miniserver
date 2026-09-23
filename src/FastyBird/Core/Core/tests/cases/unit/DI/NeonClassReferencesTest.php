<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use function assert;
use function class_exists;
use function constant;
use function count;
use function dirname;
use function enum_exists;
use function explode;
use function file_get_contents;
use function glob;
use function implode;
use function interface_exists;
use function is_dir;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use const GLOB_ONLYDIR;

/**
 * No gate resolves a class name spelled out inside a NEON value -- not PHPStan (it does not
 * parse NEON), not `make cs` (same reason), not `make naming` (it scans .php files only). A
 * `decorator:` key, a service `factory:`/`type:`/`class:` value or a DBAL `types:` entry that
 * names a class which no longer exists is not a parse error to Nette's DI container -- for
 * `decorator:` specifically it is a silent no-op (see #459, PR #455). This test is what turns
 * that into a red PHPUnit assertion.
 *
 * It intentionally checks every class name it finds under the FastyBird vendor namespace, not
 * only ones belonging to Core, because the same `decorator:` trap applies to any package's own
 * classes.
 *
 * Two shapes are deliberately excluded, because they name a NAMESPACE rather than a class:
 *
 * - Doctrine ORM mapping `namespace:` entries naming a FastyBird namespace (paired with a
 *   `directories:` list a few lines above). A stale one breaks entity mapping loudly --
 *   caught by the ORM's own metadata loading in the tests that use those fixtures -- so it
 *   does not need this test's silent-failure protection.
 * - `fbCore.application.documents.mapping` entries, which pair a FastyBird namespace key with
 *   its directory on the same line (a `…\Dummy: %appDir%/fixtures/dummy` shape). Same
 *   reasoning.
 */
final class NeonClassReferencesTest extends TestCase
{

	/**
	 * A regex that stops matching, or a scan that stops walking, would report success over
	 * zero findings -- exactly the false-green this repository has been bitten by before
	 * (`tools/check-naming.php` carries the same kind of floor for the same reason). These
	 * floors are set well below the measured counts so ordinary config churn does not trip
	 * them, while a broken scanner does.
	 */
	private const int MINIMUM_NEON_FILES = 30;

	private const int MINIMUM_CLASS_REFERENCES = 100;

	/**
	 * Built by concatenation, not written as one literal token. `tools/layering.php`'s scan
	 * (`make layers`) reads this package's own source text for the vendor word immediately
	 * followed by a namespace separator and expects a readable TYPE segment after that --
	 * correctly, for a real reference. The regex fragments below only ever match that shape
	 * inside a .neon file's text, they do not reference a class, so spelling the prefix out
	 * directly here would make this file report as an unreadable package coordinate against
	 * its own pattern text.
	 */
	private const string FASTYBIRD_NAMESPACE_PREFIX = 'FastyBird'
		. '\\\\';

	/**
	 * @throws Error
	 * @throws UnexpectedValueException
	 */
	public function testEveryClassNameInEveryNeonFileResolves(): void
	{
		$vendorDir = constant('FB_VENDOR_DIR');
		assert(is_string($vendorDir));

		$repoRoot = dirname($vendorDir);

		$files = self::collectNeonFiles($repoRoot);

		self::assertGreaterThanOrEqual(
			self::MINIMUM_NEON_FILES,
			count($files),
			sprintf(
				'scanned only %d .neon files; expected at least %d -- the file scanner may be broken',
				count($files),
				self::MINIMUM_NEON_FILES,
			),
		);

		$failures = [];
		$checked = 0;

		foreach ($files as $file) {
			$content = file_get_contents($file);
			assert(is_string($content));

			$relative = substr($file, strlen($repoRoot) + 1);

			foreach (self::extractClassReferences($content) as [$class, $line]) {
				++$checked;

				if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
					$failures[] = sprintf('%s:%d references unknown class "%s"', $relative, $line, $class);
				}
			}
		}

		self::assertGreaterThan(
			self::MINIMUM_CLASS_REFERENCES,
			$checked,
			sprintf(
				'found only %d class references across the scanned .neon files; expected more than %d -- '
				. 'the reference scanner may be broken',
				$checked,
				self::MINIMUM_CLASS_REFERENCES,
			),
		);

		self::assertSame([], $failures, implode("\n", $failures));
	}

	/**
	 * @return array<string>
	 *
	 * @throws UnexpectedValueException
	 */
	private static function collectNeonFiles(string $repoRoot): array
	{
		$roots = [
			$repoRoot . '/config',
			$repoRoot . '/tests/config',
		];

		$found = glob($repoRoot . '/src/FastyBird/*/*', GLOB_ONLYDIR);
		$packageDirs = $found !== false ? $found : [];

		foreach ($packageDirs as $packageDir) {
			$roots[] = $packageDir . '/config';
			$roots[] = $packageDir . '/tests';
		}

		$files = [];

		foreach ($roots as $root) {
			if (!is_dir($root)) {
				continue;
			}

			$iterator = new RecursiveIteratorIterator(
				new RecursiveCallbackFilterIterator(
					new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
					// A package's own `assets/` may vendor a JS toolchain with its own
					// node_modules; nothing under either is ours to scan.
					static fn (SplFileInfo $entry): bool => $entry->getFilename() !== 'node_modules'
						&& $entry->getFilename() !== 'vendor',
				),
			);

			foreach ($iterator as $entry) {
				assert($entry instanceof SplFileInfo);

				if ($entry->isFile() && $entry->getExtension() === 'neon') {
					$files[] = $entry->getPathname();
				}
			}
		}

		sort($files);

		return $files;
	}

	/**
	 * @return array<array{0: string, 1: int}>
	 */
	private static function extractClassReferences(string $content): array
	{
		$references = [];

		foreach (explode("\n", $content) as $index => $line) {
			$trimmed = trim($line);

			if ($trimmed === '' || str_starts_with($trimmed, '#')) {
				continue;
			}

			if (preg_match('/^namespace\s*:\s*' . self::FASTYBIRD_NAMESPACE_PREFIX . '/', $trimmed) === 1) {
				continue;
			}

			if (
				preg_match(
					'/^\\\\?' . self::FASTYBIRD_NAMESPACE_PREFIX . '[A-Za-z0-9_\\\\]+\s*:\s*%(?:appDir|wwwDir)%/',
					$trimmed,
				) === 1
			) {
				continue;
			}

			if (
				preg_match_all(
					'/\\\\?' . self::FASTYBIRD_NAMESPACE_PREFIX . '[A-Za-z0-9_\\\\]+/',
					$line,
					$matches,
				) === 0
			) {
				continue;
			}

			foreach ($matches[0] as $match) {
				$references[] = [ltrim($match, '\\'), $index + 1];
			}
		}

		return $references;
	}

}
