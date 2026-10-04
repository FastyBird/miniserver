<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use RuntimeException;
use function array_key_exists;
use function constant;
use function defined;
use function escapeshellarg;
use function is_array;
use function is_string;
use function json_decode;
use function shell_exec;
use function sprintf;
use function strval;
use function sys_get_temp_dir;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Runs one probe of bootstrap-e5-probes.php in a child process at production scope and returns
 * what it reported. See EntityMappingTest for why the production scope cannot be booted
 * in-process.
 */
trait ProductionProbe
{

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function probe(string $name): mixed
	{
		// The production container is compiled once and then loaded from the temp directory with
		// no freshness check (debugMode is off), so the default var/temp would hand the probe
		// whatever container an earlier boot left there. The suite run's own temp directory is
		// new for every run; the probes share one, and Nette locks it while it compiles.
		$tempDir = (defined('FB_TEMP_DIR') ? strval(constant('FB_TEMP_DIR')) : sys_get_temp_dir())
			. '/e5-production-probes';

		$command = sprintf(
			'FB_APP_DIR=%s FB_TEMP_DIR=%s FB_APP_PARAMETER__SECURITY_SIGNATURE=%s %s %s %s 2>&1',
			escapeshellarg(__DIR__ . '/../../..'),
			escapeshellarg($tempDir),
			escapeshellarg('e5-probes-test-not-a-real-signature'),
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/bootstrap-e5-probes.php'),
			escapeshellarg($name),
		);

		$output = shell_exec($command);

		if (!is_string($output)) {
			throw new RuntimeException('Could not boot the application at production scope');
		}

		$decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($decoded) || !array_key_exists('error', $decoded) || !array_key_exists('result', $decoded)) {
			throw new RuntimeException(sprintf('The production-scope probe did not report a result: %s', $output));
		}

		if ($decoded['error'] !== null) {
			throw new RuntimeException(
				sprintf(
					'The production-scope probe "%s" failed: %s',
					$name,
					is_string($decoded['error']) ? $decoded['error'] : 'unknown',
				),
			);
		}

		return $decoded['result'];
	}

}
