<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use FastyBird\Core\DI\CoreExtension;
use FastyBird\Core\Tests;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionClassConstant;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function file;
use function implode;
use function in_array;
use function is_string;
use function sort;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;
use const FILE_IGNORE_NEW_LINES;

/**
 * No Core DI identifier carries the name of a library Core was assembled from.
 *
 * `make naming` guards PHP namespaces, type names and imports. It cannot see a service name,
 * a configuration key or a tag, which are strings. This test checks those three against the
 * denylist below: every service name Core registers in its own test container, every key path
 * in CoreExtension's configuration schema, and every tag CoreExtension defines.
 *
 * VIOLATIONS_FILE lists the violations that exist today. It may only shrink: the test fails
 * on a violation that is not in the file, and on an entry in the file that is no longer a
 * violation, so a fixed name has to be taken out of the list in the same change. Epic #459
 * empties it.
 */
final class IdentifierGuardTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * The library names, in the camelCase form a DI identifier uses. A dot-separated segment of
	 * a service name, key path or tag violates if it is one of these, ignoring case (so the
	 * `jsonapi` in fbCore.jsonApi.middlewares.jsonapi counts too). A segment that merely
	 * contains one (classMetadataFactory) does not: inside a longer name the word describes,
	 * the same rule `make naming` applies to type names.
	 *
	 * The census of Epic #459 owns this list.
	 */
	public const array DENYLIST = [
		'simpleAuth',
		'jsonApi',
		'wsServer',
		'httpServer',
		'webServer',
		'dateTimeFactory',
		'doctrineCrud',
		'doctrineTimestampable',
		'doctrinePhone',
		'tools',
		'ipub',
		'metadata',
	];

	/**
	 * `application` names the library only where the library put it: as a top-level
	 * configuration section, and as the service-name segment directly under fbCore. Anywhere
	 * else (security.enable.nette.application) it is Nette's Application.
	 */
	public const string POSITIONAL_ENTRY = 'application';

	/**
	 * Core registers these without the fbCore prefix
	 */
	private const array UNPREFIXED_SERVICES = [
		'document.',
	];

	private const string VIOLATIONS_FILE = __DIR__ . '/identifier-guard-violations.txt';

	public function testNoNewAndNoFixedViolations(): void
	{
		$actual = array_merge(
			$this->serviceViolations(),
			self::configurationViolations(),
			self::tagViolations(),
		);
		sort($actual);

		$lines = file(self::VIOLATIONS_FILE, FILE_IGNORE_NEW_LINES);
		self::assertIsArray($lines);

		$expected = array_values(array_filter(
			array_map(static fn (string $line): string => trim($line), $lines),
			static fn (string $line): bool => $line !== '' && !str_starts_with($line, '#'),
		));

		self::assertSame(
			[],
			array_values(array_diff($actual, $expected)),
			'New DI identifiers carry a library name. Name them for their capability instead.',
		);

		self::assertSame(
			[],
			array_values(array_diff($expected, $actual)),
			sprintf('These are no longer violations; remove them from %s.', self::VIOLATIONS_FILE),
		);

		self::assertSame(array_values(array_unique($expected)), $expected, 'Duplicate entries');
	}

	/**
	 * @return list<string>
	 */
	private function serviceViolations(): array
	{
		$violations = [];

		foreach (array_keys($this->container->getServiceDescriptors()) as $name) {
			$isCore = str_starts_with($name, CoreExtension::NAME . '.');

			foreach (self::UNPREFIXED_SERVICES as $prefix) {
				$isCore = $isCore || str_starts_with($name, $prefix);
			}

			if (!$isCore) {
				continue;
			}

			$segments = explode('.', $name);

			if (
				self::denied($segments)
				|| ($segments[0] === CoreExtension::NAME && ($segments[1] ?? null) === self::POSITIONAL_ENTRY)
			) {
				$violations[] = 'service ' . $name;
			}
		}

		return $violations;
	}

	/**
	 * A key path is reported where its denied segment is, once, not again for every key below it
	 *
	 * @return list<string>
	 */
	private static function configurationViolations(): array
	{
		$violations = [];

		foreach (self::keyPaths((new CoreExtension())->getConfigSchema(), []) as $path) {
			$last = $path[count($path) - 1];

			if (
				self::denied([$last])
				|| (count($path) === 1 && $last === self::POSITIONAL_ENTRY)
			) {
				$violations[] = 'config ' . implode('.', $path);
			}
		}

		return $violations;
	}

	/**
	 * @return list<string>
	 */
	private static function tagViolations(): array
	{
		$violations = [];

		$constants = (new ReflectionClass(CoreExtension::class))->getReflectionConstants(
			ReflectionClassConstant::IS_PUBLIC,
		);

		foreach ($constants as $constant) {
			$value = $constant->getValue();

			if ($constant->getName() === 'NAME' || !is_string($value)) {
				continue;
			}

			if (self::denied(explode('.', $value))) {
				$violations[] = 'tag ' . $value;
			}
		}

		return $violations;
	}

	/**
	 * @param list<string> $path
	 *
	 * @return list<list<string>>
	 */
	private static function keyPaths(Schema $schema, array $path): array
	{
		if (!$schema instanceof Structure) {
			return [];
		}

		$paths = [];

		foreach ($schema->getShape() as $key => $item) {
			$itemPath = [...$path, (string) $key];
			$paths[] = $itemPath;
			$paths = array_merge($paths, self::keyPaths($item, $itemPath));
		}

		return $paths;
	}

	/**
	 * @param array<string> $segments
	 */
	private static function denied(array $segments): bool
	{
		$denylist = array_map(static fn (string $entry): string => strtolower($entry), self::DENYLIST);

		foreach ($segments as $segment) {
			if (in_array(strtolower($segment), $denylist, true)) {
				return true;
			}
		}

		return false;
	}

}
