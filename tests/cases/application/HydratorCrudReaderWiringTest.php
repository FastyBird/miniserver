<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use FastyBird\Core\Api\Helpers\CrudReader;
use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function constant;
use function defined;
use function escapeshellarg;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function shell_exec;
use function sprintf;
use function strval;
use function sys_get_temp_dir;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Asserts, at production scope, that every JSON:API hydrator is handed the CrudReader (#552).
 *
 * Hydrator takes the reader as an optional constructor argument and enforces #[Crud] only when
 * it has one. A hydrator subclass that declares its own constructor and forgets to pass the
 * reader on, or a reader service that stops being registered, silently switches enforcement
 * off: nothing errors, writes are just no longer checked. Until #552 the service was never
 * registered at all, and the Ui widget and Triggers SMS hydrators dropped the argument.
 */
final class HydratorCrudReaderWiringTest extends TestCase
{

	/**
	 * One per package that ships a hydrator, and the two whose constructors used to drop the
	 * reader. Their presence keeps the "every hydrator" assertion from passing vacuously over an
	 * enumeration that came back short.
	 */
	private const array REPRESENTATIVES = [
		'fbAccountsModule.hydrators.accounts',
		'fbDevicesModule.hydrators.device.generic',
		'fbDevicesModuleUiModuleBridge.hydrators.dataSources.channelProperty',
		'fbNsPanelConnector.hydrators.channel.battery',
		'fbTriggersModule.hydrators.notifications.sms',
		'fbUiModule.hydrators.widgets.analogSensor',
		'fbVirtualThermostatAddon.hydrators.device',
	];

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEveryHydratorReceivesTheCrudReader(): void
	{
		$result = $this->bootProductionScope();

		self::assertNull($result['error']);
		self::assertTrue(
			$result['readerRegistered'],
			'fbCore.api.helpers.crudReader is not registered, so no hydrator enforces #[Crud].',
		);

		foreach (self::REPRESENTATIVES as $name) {
			self::assertArrayHasKey($name, $result['hydrators']);
			self::assertSame(CrudReader::class, $result['hydrators'][$name], $name);
		}

		$withoutReader = array_keys(array_filter(
			$result['hydrators'],
			static fn (string|null $reader): bool => $reader !== CrudReader::class,
		));

		self::assertSame(
			[],
			$withoutReader,
			sprintf(
				"These hydrators were not handed the CrudReader, so they do not enforce #[Crud]:\n%s",
				implode("\n", $withoutReader),
			),
		);
	}

	/**
	 * @return array{error: string|null, readerRegistered: bool, hydrators: array<string, string|null>}
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function bootProductionScope(): array
	{
		// The production container is compiled once and then loaded from the temp directory with
		// no freshness check (debugMode is off), so the default var/temp would hand this test
		// whatever container an earlier boot left there. The suite run's own temp directory is
		// new for every run.
		$tempDir = (defined('FB_TEMP_DIR') ? strval(constant('FB_TEMP_DIR')) : sys_get_temp_dir())
			. '/hydrator-crud-reader-wiring';

		$command = sprintf(
			'FB_APP_DIR=%s FB_TEMP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s 2>&1',
			escapeshellarg(__DIR__ . '/../../..'),
			escapeshellarg($tempDir),
			escapeshellarg('hydrator-wiring-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-hydrators.php'),
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		$decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		if (
			!is_array($decoded)
			|| !array_key_exists('error', $decoded)
			|| !(is_string($decoded['error']) || $decoded['error'] === null)
			|| !is_bool($decoded['readerRegistered'] ?? null)
			|| !is_array($decoded['hydrators'] ?? null)
		) {
			throw new RuntimeException(sprintf('Production-scope boot did not report a result: %s', $output));
		}

		$hydrators = [];

		foreach ($decoded['hydrators'] as $name => $reader) {
			if (!is_string($name) || !(is_string($reader) || $reader === null)) {
				throw new RuntimeException('Production-scope boot reported a malformed hydrator list');
			}

			$hydrators[$name] = $reader;
		}

		return [
			'error' => $decoded['error'],
			'readerRegistered' => $decoded['readerRegistered'],
			'hydrators' => $hydrators,
		];
	}

}
