<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\ValueObject\PhpVersion;

/**
 * E5.9 (#641): adopt the PSR-20 clock. Copied from tools/rector/skeleton.php, which documents
 * the paths, the skips and why names are not imported.
 *
 * Two rules, nothing else (#460 §3.7):
 *
 *   - RenameMethodRector: getNow() becomes now() ON FastyBird\Core\Clock\Clock ONLY. The rename
 *     is type-aware -- a call is renamed when its receiver's type is that interface or one of its
 *     implementations (SystemClock, FrozenClock, a mock of the interface), and a declaration when
 *     its class implements it. Any other class's getNow() is left alone: Accounts' private
 *     SessionV1::getNow() is the one that exists today. A string method name (a PHPUnit mock's
 *     ->method('getNow')) is not a method call, so Rector leaves it to the hand fixes.
 *   - RenameClassRector: FastyBird\Core\Clock\Clock becomes Psr\Clock\ClockInterface in every
 *     type position, `implements` list, `::class` fetch and docblock.
 *
 * Both rules run in one pass over the original tree, so the method rule still sees the old type
 * in PHPStan's scope while the class rule rewrites its name.
 *
 *   make rector-e5 RECTOR_CONFIG=tools/rector/e5-clock.php
 */
return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/../../src/FastyBird',
		__DIR__ . '/../../tests',
		__DIR__ . '/../../bin',
		__DIR__ . '/../../public',
		__DIR__ . '/../../migrations',
	])
	->withSkip([
		'*/node_modules/*',
		'*/assets/*',
		__DIR__ . '/../../tests/stubs',
	])
	->withCache(__DIR__ . '/../../var/tools/Rector')
	->withPhpVersion(PhpVersion::PHP_84)
	->withConfiguredRule(RenameMethodRector::class, [
		new MethodCallRename('FastyBird\\Core\\Clock\\Clock', 'getNow', 'now'),
	])
	->withConfiguredRule(RenameClassRector::class, [
		'FastyBird\\Core\\Clock\\Clock' => 'Psr\\Clock\\ClockInterface',
	]);
