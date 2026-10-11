<?php declare(strict_types = 1);

if (!defined('FB_APP_DIR')) {
	define('FB_APP_DIR', 'FB_APP_DIR');
}
if (!defined('FB_RESOURCES_DIR')) {
	define('FB_RESOURCES_DIR', 'FB_RESOURCES_DIR');
}
if (!defined('FB_PUBLIC_DIR')) {
	define('FB_PUBLIC_DIR', 'FB_PUBLIC_DIR');
}
if (!defined('FB_TEMP_DIR')) {
	define('FB_TEMP_DIR', 'FB_TEMP_DIR');
}
if (!defined('FB_LOGS_DIR')) {
	define('FB_LOGS_DIR', 'FB_LOGS_DIR');
}
if (!defined('FB_CONFIG_DIR')) {
	define('FB_CONFIG_DIR', 'FB_CONFIG_DIR');
}

// No DG\BypassFinals::enable() here, on purpose (#728). PHPStan reads source statically and
// nothing it loads at run time needs `final` or `readonly` stripped. BypassFinals' default strips
// both from every file PHP loads, so a class PHPStan reflects at run time instead of parsing can
// lose `readonly`, and its `readonly` children are then reported as `class.nonReadOnly`. Only the
// test runner needs the bypass, to mock final and readonly classes: tools/phpunit-bootstrap.php.
