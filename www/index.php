<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use FastyBird\Bootstrap\Boot;
use FastyBird\MiniServer\Application;

if (getenv('FB_APP_DIR') !== FALSE) {
	$vendorDir = realpath(getenv('FB_APP_DIR') . '/vendor');
	$envDir = realpath(getenv('FB_APP_DIR') . '/env');

} else {
	$vendorDir = realpath(__DIR__ . '/../vendor');
	$envDir = realpath(__DIR__ . '/../env');
}

$autoload = $vendorDir . '/autoload.php';

if (file_exists($autoload)) {
	require $autoload;

	if ($envDir !== false) {
		try {
			$dotEnv = Dotenv::createImmutable($envDir);
			$dotEnv->load();

		} catch (Throwable $ex) {
			// Env files could not be loaded
		}
	}

	$configurator = Boot\Bootstrap::boot();

	$configurator
		->createContainer()
		->getByType(Application\IApplication::class)
		->run();

} else {
	echo 'Composer autoload not found!';
}
