<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

/**
 * Rector is scoped deliberately narrowly.
 *
 * It is here to convert PHPUnit's doc-block annotations to attributes, which has to happen
 * before PHPUnit 11 removes support for the annotations. It is not a general-purpose
 * refactoring pass over the codebase: the paths cover test directories only, and the single
 * set is the PHPUnit annotation conversion. Widening either is a deliberate decision, not a
 * side effect of running `make rector`.
 *
 * PHP_CodeSniffer, not Rector, owns formatting -- run `make csf` after any Rector pass.
 */
return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/src/FastyBird/Addon',
		__DIR__ . '/src/FastyBird/Automator',
		__DIR__ . '/src/FastyBird/Bridge',
		__DIR__ . '/src/FastyBird/Connector',
		__DIR__ . '/src/FastyBird/Core',
		__DIR__ . '/src/FastyBird/Module',
		__DIR__ . '/src/FastyBird/Plugin',
		__DIR__ . '/tests',
	])
	->withSkip([
		// Only the test suites carry PHPUnit annotations; keep production code untouched.
		// Note the pattern has to name the per-package src directory. Everything in this
		// repository lives under src/FastyBird, so a bare '*/src/*' skips the entire tree
		// and Rector reports success having changed nothing.
		__DIR__ . '/src/FastyBird/*/*/src',
		__DIR__ . '/src/FastyBird/*/*/node_modules',
	])
	->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_84)
	->withSets([
		PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
	]);
