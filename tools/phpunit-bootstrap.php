<?php declare(strict_types = 1);

// phpcs:ignoreFile

define('FB_APP_DIR', realpath(__DIR__ . '/..'));
define('FB_CONFIG_DIR', FB_APP_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'config');
define('FB_VENDOR_DIR', realpath(__DIR__ . '/../vendor'));
is_string(getenv('TEST_TOKEN'))
	? define('FB_TEMP_DIR', __DIR__ . '/../var/tools/PHPUnit/tmp/' . getmypid() . '-' . getenv('TEST_TOKEN') ?? '')
	: define('FB_TEMP_DIR', __DIR__ . '/../var/tools/PHPUnit/tmp/' . getmypid());

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Tester using `composer update --dev`';
	exit(1);
}

DG\BypassFinals::enable();
