<?php declare(strict_types = 1);

/**
 * EntityMappingTest.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Tests
 * @since          1.0.0
 *
 * @date           12.09.26
 */

namespace FastyBird\MiniServer\Tests\Cases\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_slice;
use function escapeshellarg;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function shell_exec;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Boots the application the way production boots it -- every extension registered by
 * config/common.neon -- and asserts that the resulting Doctrine metadata is sound.
 *
 * Every other test in this repository boots exactly ONE extension, because
 * tools/phpunit-bootstrap.php points FB_APP_DIR at <repo>/tests, which has no config/
 * directory, so Bootstrap::resolveConfigFiles() silently skips config/common.neon and its
 * 45 extension registrations. Production registers 22 extensions. Every mapping defect this
 * project has shipped lives on that axis, invisible to a green suite.
 *
 * This runs in a CHILD PROCESS on purpose. FB_APP_DIR is a constant, the PHPUnit bootstrap
 * has already defined it by the time any test runs, and Bootstrap::initConstants() honours
 * an existing definition. Re-pointing it in-process is impossible; re-implementing the boot
 * sequence here would drift from the real one. Driving the real Bootstrap::boot() with the
 * environment variable set is the only version that tests what production actually does.
 *
 * No database is required: validateMapping() reads metadata only.
 */
final class EntityMappingTest extends TestCase
{

	/**
	 * Snapshot of the discriminator maps at production scope.
	 *
	 * These are NOT arbitrary. A discriminator map is assembled from every registered
	 * extension, so at single-extension test scope these three report 3, 3 and 3. An
	 * extension that fails to register, or two extensions that collide on a const TYPE,
	 * silently shrink the map -- and a shrunken map does not throw. It makes find() return
	 * null for rows that exist, because the persister emits WHERE <type> IN (...) from the
	 * map. That is a production outage no per-package suite can see.
	 */
	private const EXPECTED_DISCRIMINATORS = [
		'FastyBird\Module\Devices\Entities\Connectors\Connector' => 12,
		'FastyBird\Module\Devices\Entities\Devices\Device' => 18,
		'FastyBird\Module\Devices\Entities\Channels\Channel' => 50,
	];

	private const EXPECTED_METADATA_CLASSES = 145;

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testMappingIsValidAtProductionScope(): void
	{
		$result = $this->bootProductionScope();

		self::assertSame(
			[],
			$result['errors'],
			sprintf(
				"Doctrine reports %d class(es) with invalid mapping when every extension is "
				. "registered. The single-extension suites cannot see this.\n\n%s",
				$result['classesInError'],
				implode("\n\n", array_slice($result['errorText'], 0, 5)),
			),
		);
	}

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryExtensionContributesItsEntities(): void
	{
		$result = $this->bootProductionScope();

		// A floor rather than an equality: adding an entity is routine, losing 20 of them
		// because an extension stopped registering is not.
		self::assertGreaterThanOrEqual(
			self::EXPECTED_METADATA_CLASSES,
			$result['metadataClasses'],
			'Fewer entity classes than production scope should produce. An extension is '
			. 'probably no longer registered in config/common.neon.',
		);
	}

	/**
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testDiscriminatorMapsAreComplete(): void
	{
		$result = $this->bootProductionScope();

		foreach (self::EXPECTED_DISCRIMINATORS as $class => $expected) {
			self::assertSame(
				$expected,
				$result['discriminators'][$class] ?? null,
				sprintf(
					'%s should resolve %d subtypes at production scope. A smaller map makes '
					. 'find() return null for rows that exist, with no error anywhere.',
					$class,
					$expected,
				),
			);
		}
	}

	/**
	 * @return array{metadataClasses: int, classesInError: int, errors: list<string>, errorText: list<string>, discriminators: array<string, int>}
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function bootProductionScope(): array
	{
		$root = __DIR__ . '/../../..';
		$script = __DIR__ . '/bootstrap-production-scope.php';

		$command = sprintf(
			'FB_APP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s 2>&1',
			escapeshellarg($root),
			escapeshellarg('entity-mapping-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg($script),
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		$decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($decoded)) {
			throw new RuntimeException(sprintf('Production-scope boot did not report a result: %s', $output));
		}

		// Narrowed field by field rather than cast wholesale: json_decode yields mixed, and a
		// payload this test asserts against should be validated where it enters the process.
		// A malformed report is a broken harness, and should say so rather than fail as a
		// confusing assertion mismatch further down.
		$discriminators = [];

		foreach ($this->arrayField($decoded, 'discriminators') as $class => $count) {
			if (!is_string($class) || !is_int($count)) {
				throw new RuntimeException('Production-scope boot reported a malformed discriminator map');
			}

			$discriminators[$class] = $count;
		}

		return [
			'metadataClasses' => $this->intField($decoded, 'metadataClasses'),
			'classesInError' => $this->intField($decoded, 'classesInError'),
			'errors' => $this->stringListField($decoded, 'errors'),
			'errorText' => $this->stringListField($decoded, 'errorText'),
			'discriminators' => $discriminators,
		];
	}

	/**
	 * @param array<mixed> $payload
	 *
	 * @throws RuntimeException
	 */
	private function intField(array $payload, string $field): int
	{
		$value = $payload[$field] ?? null;

		if (!is_int($value)) {
			throw new RuntimeException(sprintf('Production-scope boot reported no integer "%s"', $field));
		}

		return $value;
	}

	/**
	 * @param array<mixed> $payload
	 *
	 * @return array<mixed>
	 *
	 * @throws RuntimeException
	 */
	private function arrayField(array $payload, string $field): array
	{
		$value = $payload[$field] ?? null;

		if (!is_array($value)) {
			throw new RuntimeException(sprintf('Production-scope boot reported no array "%s"', $field));
		}

		return $value;
	}

	/**
	 * @param array<mixed> $payload
	 *
	 * @return list<string>
	 *
	 * @throws RuntimeException
	 */
	private function stringListField(array $payload, string $field): array
	{
		$values = [];

		foreach ($this->arrayField($payload, $field) as $value) {
			if (!is_string($value)) {
				throw new RuntimeException(sprintf('Production-scope boot reported a malformed "%s"', $field));
			}

			$values[] = $value;
		}

		return $values;
	}

}
