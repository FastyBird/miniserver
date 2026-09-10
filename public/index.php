<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use FastyBird\Core\Application\Boot;
use FastyBird\Library\Metadata;
use FastyBird\Plugin\WebServer\Application as WebServerApplication;
use Nette\Application as NetteApplication;

if (isset($_ENV['FB_APP_DIR'])) {
	$vendorDir = realpath($_ENV['FB_APP_DIR'] . DIRECTORY_SEPARATOR . 'vendor');
	$envDir = realpath($_ENV['FB_APP_DIR'] . DIRECTORY_SEPARATOR . 'env');

} else {
	$vendorDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor');
	$envDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'env');

	define('FB_APP_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));
	define('FB_PUBLIC_DIR', realpath(__DIR__));
}

$autoload = $vendorDir . DIRECTORY_SEPARATOR . 'autoload.php';

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

	$isApi = substr($_SERVER['REQUEST_URI'], 0, 4) === '/' . Metadata\Constants::ROUTER_API_PREFIX;

	$container = $configurator->createContainer();

	if ($isApi) {
		// WebServer application
		$container
			->getByType(WebServerApplication\Application::class)
			->run();
	} else {
		// Nette application
		$container
			->getByType(NetteApplication\Application::class)
			->run();
	}

} else {
	echo 'Composer autoload not found!';
}
