<?php declare(strict_types = 1);

use FastyBird\Core\Boot;
use Symfony\Component\Console;

$autoloadFile = false;

$path = __DIR__;

for ($i = 0;$i < 10;$i++) {
	$path .= DIRECTORY_SEPARATOR . '..';

	$vendorPath = realpath($path . DIRECTORY_SEPARATOR . 'vendor');

	if ($vendorPath !== false) {
		$autoloadFile = realpath($path) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

		break;
	}
}

if ($autoloadFile === false || !file_exists($autoloadFile)) {
	echo "Autoload file not found; try 'composer dump-autoload' first." . PHP_EOL;

	exit(1);
}

require $autoloadFile;

$boostrap = Boot\Bootstrap::boot();

$container = $boostrap->createContainer();

$console = $container->getByType(Console\Application::class);

// Clear cache
exit($console->run());
