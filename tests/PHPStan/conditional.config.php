<?php declare(strict_types = 1);

// Empty today, and kept as a variable rather than `return []` so a conditional entry can be
// added exactly as in the example below.
// phpcs:ignore SlevomatCodingStandard.Variables.UselessVariable.UselessVariable
$config = [];

/*
 * Example:
if (PHP_VERSION_ID >= 8_02_00) {
	// Change of signature in PHP 8.2
	$config['parameters']['ignoreErrors'][] = [
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		'message' => '~Method FastyBird\\\\Library\\\\Application\\\\Helpers\\\\Database::reconnect\(\) has Doctrine\\\\DBAL\\\\Exception in PHPDoc @throws tag but it\'s not thrown.~',
		'path' => __DIR__ . '/../../src/FastyBird/Library/Application/src/Helpers/Database.php',
		'count' => 1,
	];
}
*/

return $config;
