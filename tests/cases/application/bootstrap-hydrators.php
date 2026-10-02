<?php declare(strict_types = 1);

/**
 * Boots the application exactly as public/index.php does -- Bootstrap::boot() with FB_APP_DIR
 * pointing at the repository root, so config/common.neon and every extension it registers
 * load -- then instantiates every JSON:API hydrator service and reports, as JSON on stdout,
 * whether each one was handed the CrudReader.
 *
 * Run as a child process by HydratorCrudReaderWiringTest; see EntityMappingTest for why the
 * production scope cannot be booted in-process.
 */

// The deprecation notices vendor emits on PHP 8.4 would otherwise be interleaved with the
// JSON this script writes to stdout. See tools/php.d/tests.ini.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Boot;

$report = static function (array $payload): never {
	echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

	exit(0);
};

try {
	$configurator = Boot\Bootstrap::boot();

	// Same override, same fixture and same reason as bootstrap-production-scope.php
	$configurator->addConfig([
		'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
	]);

	$container = $configurator->createContainer();

	$crudReader = new ReflectionProperty(Hydrators\Hydrator::class, 'crudReader');

	$hydrators = [];

	foreach ($container->findByType(Hydrators\Hydrator::class) as $name) {
		$hydrator = $container->getService($name);
		assert($hydrator instanceof Hydrators\Hydrator);

		$reader = $crudReader->getValue($hydrator);

		$hydrators[$name] = is_object($reader) ? $reader::class : null;
	}

	$report([
		'error' => null,
		'readerRegistered' => $container->hasService('fbCore.api.helpers.crudReader'),
		'hydrators' => $hydrators,
	]);
} catch (Throwable $ex) {
	$report([
		'error' => $ex::class . ': ' . $ex->getMessage(),
		'readerRegistered' => false,
		'hydrators' => [],
	]);
}
