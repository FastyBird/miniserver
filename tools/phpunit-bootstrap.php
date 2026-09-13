<?php declare(strict_types = 1);

// phpcs:ignoreFile

define('FB_APP_DIR', realpath(__DIR__ . '/../tests'));
define('FB_CONFIG_DIR', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'config');
define('FB_VENDOR_DIR', realpath(__DIR__ . '/../vendor'));
// One directory per suite RUN, shared by every paratest worker and by every child process
// PHPUnit forks for @runTestsInSeparateProcesses, which re-run this file.
//
// It has to be per run and not merely stable. Boot\Configurator keys the compiled DI
// container on a hash of the static parameters, tempDir among them, so a directory built
// from getmypid() and md5(time()) guaranteed a miss and a full container compile for every
// single test. But APP_ENV is not 'dev' under the test runner, so debugMode is false,
// autoRebuild is false, and Nette takes ContainerLoader::loadOnce() -- which returns the
// cached container on nothing more than class_exists() or a successful include, with no
// mtime check. A directory that outlived the run would therefore serve a stale container
// for ever, and editing a .neon or an entity mapping would stop having any effect.
//
// The Makefile exports FB_TEST_RUN_ID for its test targets. Running phpunit directly
// without it falls back to the old per-process behaviour: correct, just slower.
$fbTestRunId = getenv('FB_TEST_RUN_ID');

$fbTestRun = is_string($fbTestRunId) && $fbTestRunId !== ''
	? $fbTestRunId
	: getmypid() . '-' . md5((string) time());

define('FB_TEMP_DIR', __DIR__ . '/../var/tools/PHPUnit/tmp/' . $fbTestRun);
define('FB_LOGS_DIR', __DIR__ . '/../var/tools/PHPUnit/logs/' . $fbTestRun);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Tester using `composer update --dev`';
	exit(1);
}

DG\BypassFinals::enable();
