<?php declare(strict_types = 1);

/**
 * bootstrap-production-scope.php
 *
 * Boots the application exactly as public/index.php does -- Bootstrap::boot() with
 * FB_APP_DIR pointing at the repository root, so config/common.neon and all 45 extension
 * registrations load -- and reports the Doctrine metadata as JSON on stdout.
 *
 * Run as a child process by EntityMappingTest; see the comment there for why it cannot
 * happen in-process.
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

// The deprecation notices vendor emits on PHP 8.4 would otherwise be interleaved with the
// JSON this script writes to stdout. See tools/php.d/tests.ini.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use FastyBird\Core\Application\Boot;

$report = static function (array $payload): never {
	echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

	exit(0);
};

try {
	$configurator = Boot\Bootstrap::boot();

	// contributte/vite refuses to compile without a Vite manifest, and public/manifest.json is
	// a frontend build artefact that is not tracked. Booting the container must not depend on
	// `yarn build` having run: this test is about Doctrine metadata, and the Vite extension
	// owns no entities. Pointing it at an empty manifest keeps all 45 extensions registered --
	// which is the whole point of this tier -- without coupling it to the asset pipeline.
	$configurator->addConfig([
		'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
	]);

	$container = $configurator->createContainer();

	$entityManager = $container->getByType(EntityManagerInterface::class);

	$metadata = $entityManager->getMetadataFactory()->getAllMetadata();
	$errors = (new SchemaValidator($entityManager))->validateMapping();

	$errorText = [];

	foreach ($errors as $class => $messages) {
		$errorText[] = $class . "\n  " . implode("\n  ", $messages);
	}

	$discriminators = [];

	foreach ([
		'FastyBird\Module\Devices\Entities\Connectors\Connector',
		'FastyBird\Module\Devices\Entities\Devices\Device',
		'FastyBird\Module\Devices\Entities\Channels\Channel',
	] as $class) {
		$discriminators[$class] = count($entityManager->getClassMetadata($class)->discriminatorMap);
	}

	$report([
		'metadataClasses' => count($metadata),
		'classesInError' => count($errors),
		'errors' => array_keys($errors),
		'errorText' => $errorText,
		'discriminators' => $discriminators,
	]);
} catch (Throwable $ex) {
	$report([
		'metadataClasses' => 0,
		'classesInError' => -1,
		'errors' => [$ex::class],
		'errorText' => [$ex::class . ': ' . $ex->getMessage()],
		'discriminators' => [],
	]);
}
