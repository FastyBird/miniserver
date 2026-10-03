<?php declare(strict_types = 1);

$boostrapFile = false;

$path = __DIR__;

for ($i = 0;$i < 10;$i++) {
	$path .= DIRECTORY_SEPARATOR . '..';

	$srcPath = realpath($path . DIRECTORY_SEPARATOR . 'src');

	if ($srcPath !== false) {
		$boostrapFile = implode(
			DIRECTORY_SEPARATOR,
			[realpath($path), 'src', 'FastyBird', 'Core', 'Core', 'bin', 'fb-console.php'],
		);

		break;
	}
}

if ($boostrapFile === false || !file_exists($boostrapFile)) {
	echo 'Application file not found.' . PHP_EOL;

	exit(1);
}

include $boostrapFile;
