<?php declare(strict_types = 1);

/**
 * Every Doctrine inheritance root under src/FastyBird must declare an explicit
 * #[ORM\DiscriminatorMap].
 *
 * WHY THIS IS A GATE
 *
 * Discriminator maps in this repository are assembled at runtime: Core\Application's
 * EntityDiscriminator subscriber collects #[DiscriminatorEntry] from subclasses that live in
 * other packages and appends them on the loadClassMetadata event. Doctrine, however, calls
 * ClassMetadataFactory::addDefaultDiscriminatorMap() BEFORE dispatching that event, and only
 * when the map is still empty. That default keys every subtype by its short class name.
 *
 * So a root with no explicit map gets both sets of keys: Doctrine's short-name defaults first,
 * then the subscriber's #[DiscriminatorEntry] names on top. The same class ends up reachable
 * under two discriminator values -- for example Role under both "role" and "user_role". Reads
 * of existing rows keep working, because the value the database stores is still one of the
 * keys, which is exactly why a full green test suite does not notice. The map is simply wrong.
 *
 * A non-empty map skips addDefaultDiscriminatorMap() entirely. This used to be achieved by
 * patching doctrine/orm to defer the call; the patch is gone, so the invariant is now carried
 * by the attributes alone and needs enforcing.
 *
 * Seed a new root with the entry the subscriber would have appended anyway -- its own short
 * name mapped to itself -- or with a concrete subtype the package already owns.
 *
 * Exit codes follow tools/check-layering.php:
 *   0  clean
 *   1  at least one inheritance root has no explicit discriminator map
 *   2  the tool could not do its job (self-check tripped, src/FastyBird missing)
 *
 * Plain PHP, no dependencies; neither vendor/ nor an autoloader is required.
 */

/**
 * Bail out for reasons that are the TOOL's problem rather than the repository's, so a CI log
 * distinguishes "the invariant broke" from "the gate broke".
 */
function fbFail(string $message): never
{
	fwrite(STDERR, 'check-discriminators: ' . $message . PHP_EOL);

	exit(2);
}

$root = dirname(__DIR__) . '/src/FastyBird';

if (!is_dir($root)) {
	fbFail(sprintf('"%s" is not a directory', $root));
}

/**
 * Build a matcher for one Doctrine mapping attribute, bound to however THIS file imported
 * Doctrine\ORM\Mapping. Matching any *\InheritanceType would also hit the application's own
 * document mapping attributes under src/.../Documents/, which are a separate mechanism with a
 * separate discriminator implementation and must not be reported here.
 *
 * @return array<string>
 */
function fbAttributePatterns(string $code, string $name): array
{
	$patterns = [];

	// use Doctrine\ORM\Mapping as ORM;  ->  #[ORM\InheritanceType(
	preg_match_all('/^use\s+Doctrine\\\\ORM\\\\Mapping\s+as\s+([A-Za-z_][A-Za-z0-9_]*)\s*;/m', $code, $m);

	foreach ($m[1] as $alias) {
		$patterns[] = '/#\[\s*' . preg_quote($alias, '/') . '\\\\' . $name . '\s*\(/';
	}

	// use Doctrine\ORM\Mapping\InheritanceType;  ->  #[InheritanceType(
	if (preg_match('/^use\s+Doctrine\\\\ORM\\\\Mapping\\\\' . $name . '\s*;/m', $code) === 1) {
		$patterns[] = '/#\[\s*' . $name . '\s*\(/';
	}

	// Fully qualified.
	$patterns[] = '/#\[\s*\\\\Doctrine\\\\ORM\\\\Mapping\\\\' . $name . '\s*\(/';

	return $patterns;
}

/**
 * @param array<string> $patterns
 */
function fbMatchesAny(array $patterns, string $subject): bool
{
	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $subject) === 1) {
			return true;
		}
	}

	return false;
}

$roots = 0;
$violations = [];

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($files as $file) {
	assert($file instanceof SplFileInfo);

	// Only production code of a package: src/FastyBird/<Type>/<Name>/src/...
	if (
		$file->getExtension() !== 'php'
		|| preg_match('#/src/FastyBird/[^/]+/[^/]+/src/#', $file->getPathname()) !== 1
	) {
		continue;
	}

	$code = file_get_contents($file->getPathname());

	if ($code === false) {
		fbFail(sprintf('could not read "%s"', $file->getPathname()));
	}

	if (!fbMatchesAny(fbAttributePatterns($code, 'InheritanceType'), $code)) {
		continue;
	}

	// Only consider the attribute block that precedes the class declaration, so a
	// DiscriminatorMap mentioned inside a method body cannot satisfy the check.
	$declaration = preg_split('/^\s*(?:final\s+|abstract\s+)*class\s/m', $code, 2);
	$prefix = $declaration[0] ?? $code;

	$roots++;

	if (fbMatchesAny(fbAttributePatterns($code, 'DiscriminatorMap'), $prefix)) {
		continue;
	}

	$violations[] = substr($file->getPathname(), strlen(dirname(__DIR__)) + 1);
}

// Self-check. A regex that silently stops matching would otherwise report success over zero
// files, which is the exact false-green this repository has been bitten by before. There are
// 15 roots today; require a plausible floor rather than an exact count so that adding or
// removing one hierarchy does not trip the tool.
if ($roots < 10) {
	fbFail(sprintf(
		'found only %d inheritance roots under %s; expected at least 10, so the attribute '
		. 'match is probably broken rather than the repository being clean',
		$roots,
		$root,
	));
}

if ($violations !== []) {
	sort($violations);

	fwrite(
		STDERR,
		sprintf(
			"%d of %d Doctrine inheritance roots declare no explicit #[ORM\\DiscriminatorMap]:\n\n",
			count($violations),
			$roots,
		),
	);

	foreach ($violations as $violation) {
		fwrite(STDERR, '  ' . $violation . PHP_EOL);
	}

	fwrite(
		STDERR,
		PHP_EOL
		. "Without one, Doctrine applies its short-class-name default map before the\n"
		. "EntityDiscriminator subscriber runs, and every subtype ends up in the map twice.\n"
		. "See the comment at the top of tools/check-discriminators.php.\n",
	);

	exit(1);
}

printf("Checked %d Doctrine inheritance roots; all declare an explicit discriminator map.\n", $roots);

exit(0);
