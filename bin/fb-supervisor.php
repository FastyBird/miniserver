<?php declare(strict_types = 1);

$boostrapFile = false;

$path = __DIR__;

for ($i = 0;$i < 10;$i++) {
	$path .= DIRECTORY_SEPARATOR . '..';

	$srcPath = realpath($path . DIRECTORY_SEPARATOR . 'src');

	if ($srcPath !== false) {
		$boostrapFile = realpath($path) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'FastyBird' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'fb-supervisor.php';

		break;
	}
}

if ($boostrapFile === false || !file_exists($boostrapFile)) {
	echo "Application file not found." . PHP_EOL;

	exit(1);
}

include($boostrapFile);