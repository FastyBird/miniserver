<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\DI\CoreExtension;
use FilesystemIterator;
use Nette;
use Nette\DI\Config\Loader;
use Nette\Schema;
use PHPUnit\Framework\TestCase;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use function array_key_exists;
use function array_replace_recursive;
use function assert;
use function constant;
use function count;
use function dirname;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function is_string;
use function sort;
use function sprintf;
use function strlen;
use function substr;
use const GLOB_ONLYDIR;

/**
 * Every fbCore section in the repository's NEON files is valid against CoreExtension's schema.
 *
 * A container that loads a file already fails on an unknown key, but four module
 * config/example.neon files are loaded by nothing, so a renamed key would leave them stale
 * without any error. This reads every file directly instead.
 *
 * Parameters are expanded as a container would expand them, from Core's and the application's
 * config/defaults.neon, the file's own (and included) parameters, and the static parameters
 * Boot\Bootstrap defines. security.signature is set to a non-empty string, as every real
 * deployment sets it through FB_APP_PARAMETER__SECURITY_SIGNATURE (docs/configuration.md). A
 * parameter that none of these define fails the file.
 */
final class ConfigurationFilesTest extends TestCase
{

	/**
	 * Floor, not a pin: a scanner that stops walking would otherwise pass over zero files.
	 * 35 were measured on 2026-09-27: both config/common.neon files, Core's and 28 packages'
	 * tests/common.neon, and the four example.neon files below.
	 */
	private const int MINIMUM_FILES = 35;

	private const array UNLOADED_FILES = [
		'src/FastyBird/Module/Accounts/config/example.neon',
		'src/FastyBird/Module/Devices/config/example.neon',
		'src/FastyBird/Module/Triggers/config/example.neon',
		'src/FastyBird/Module/Ui/config/example.neon',
	];

	/**
	 * @throws Error
	 * @throws UnexpectedValueException
	 */
	public function testEveryFbCoreSectionMatchesTheSchema(): void
	{
		$vendorDir = constant('FB_VENDOR_DIR');
		assert(is_string($vendorDir));

		$repoRoot = dirname($vendorDir);

		$validated = [];
		$failures = [];

		foreach (self::collectNeonFiles($repoRoot) as $file) {
			$config = (new Loader())->load($file);

			if (!array_key_exists(CoreExtension::NAME, $config)) {
				continue;
			}

			$relative = substr($file, strlen($repoRoot) + 1);
			$validated[] = $relative;

			try {
				$section = Nette\DI\Helpers::expand(
					$config[CoreExtension::NAME],
					self::parameters($repoRoot, $config),
					true,
				);

				(new Schema\Processor())->process((new CoreExtension())->getConfigSchema(), $section);
			} catch (Schema\ValidationException | Nette\InvalidArgumentException $ex) {
				$failures[] = sprintf('%s: %s', $relative, $ex->getMessage());
			}
		}

		self::assertGreaterThanOrEqual(
			self::MINIMUM_FILES,
			count($validated),
			sprintf('validated only %d files: %s', count($validated), implode(', ', $validated)),
		);

		foreach (self::UNLOADED_FILES as $file) {
			self::assertContains($file, $validated);
		}

		self::assertSame([], $failures, implode("\n", $failures));
	}

	/**
	 * @param array<mixed> $config
	 *
	 * @return array<string, mixed>
	 */
	private static function parameters(string $repoRoot, array $config): array
	{
		$parameters = [];

		foreach ([
			$repoRoot . '/src/FastyBird/Core/Core/config/defaults.neon',
			$repoRoot . '/config/defaults.neon',
		] as $defaults) {
			$loaded = (new Loader())->load($defaults)['parameters'] ?? [];
			assert(is_array($loaded));

			$parameters = array_replace_recursive($parameters, $loaded);
		}

		$own = $config['parameters'] ?? [];
		assert(is_array($own));

		/** @var array<string, mixed> $merged */
		$merged = array_replace_recursive($parameters, $own, [
			'appDir' => $repoRoot,
			'wwwDir' => $repoRoot . '/public',
			'vendorDir' => $repoRoot . '/vendor',
			'tempDir' => $repoRoot . '/var/temp',
			'logsDir' => $repoRoot . '/var/logs',
			'debugMode' => false,
			'productionMode' => true,
			'consoleMode' => true,
			'security' => ['signature' => 'configuration-files-test'],
		]);

		return $merged;
	}

	/**
	 * The same roots NeonClassReferencesTest scans. config/local.neon is git-ignored and is an
	 * operator's file, not the repository's.
	 *
	 * @return list<string>
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

		foreach ($found !== false ? $found : [] as $packageDir) {
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
					static fn (SplFileInfo $entry): bool => $entry->getFilename() !== 'node_modules'
						&& $entry->getFilename() !== 'vendor',
				),
			);

			foreach ($iterator as $entry) {
				assert($entry instanceof SplFileInfo);

				if (
					$entry->isFile()
					&& $entry->getExtension() === 'neon'
					&& $entry->getPathname() !== $repoRoot . '/config/local.neon'
				) {
					$files[] = $entry->getPathname();
				}
			}
		}

		sort($files);

		return $files;
	}

}
