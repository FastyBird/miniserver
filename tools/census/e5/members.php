<?php declare(strict_types = 1);

/**
 * E5.1 census (#633): members of Core's types, read by PHP Reflection through the real
 * autoloader (vendor/autoload.php; the mirror must be fresh, see php.sh).
 *
 *   php tools/census/e5/members.php callbacks   -- T3: every public array property of a Core
 *        class (declared under src/FastyBird/Core/Core/src) whose docblock type is a list of
 *        Closure/callable, or whose name starts with on/before/after
 *   php tools/census/e5/members.php constants   -- T5: every constant of FastyBird\Core\Constants
 *        with its value
 *   php tools/census/e5/members.php accessors   -- T6: every get/set pair: a public method
 *        get<X>()/is<X>() whose body is `return $this->p;` and a method set<X>($v) whose body
 *        is `$this->p = $v;` (optionally followed by `return $this;`), on the same property;
 *        bodies are matched on PhpToken::tokenize() of the method's source lines, whitespace and
 *        comments dropped
 */

require __DIR__ . '/../../../vendor/autoload.php';

$root = realpath(__DIR__ . '/../../..');
$coreSrc = $root . '/src/FastyBird/Core/Core/src/';
$mode = $argv[1] ?? '';

$classes = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSrc, FilesystemIterator::SKIP_DOTS));

foreach ($it as $f) {
	if ($f->getExtension() !== 'php') {
		continue;
	}

	$code = file_get_contents($f->getPathname());

	if (!preg_match('/^namespace\s+([^;]+);/m', $code, $ns)) {
		continue;
	}

	$tokens = PhpToken::tokenize($code);

	for ($i = 0; $i < count($tokens); $i++) {
		if ($tokens[$i]->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
			$j = $i - 1;

			while ($j >= 0 && $tokens[$j]->is([T_WHITESPACE, T_FINAL, T_ABSTRACT, T_READONLY])) {
				$j--;
			}

			if ($j >= 0 && $tokens[$j]->is(T_DOUBLE_COLON)) {
				continue;
			}

			for ($k = $i + 1; $k < count($tokens) && $tokens[$k]->is(T_WHITESPACE); $k++) {
			}

			if ($tokens[$k]->is(T_STRING)) {
				$classes[] = $ns[1] . '\\' . $tokens[$k]->text;
			}
		}
	}
}

sort($classes);

/** @return list<string> significant tokens of a method body */
function bodyTokens(ReflectionMethod $m): array
{
	$lines = file($m->getFileName());
	$src = implode('', array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
	$tokens = PhpToken::tokenize('<?php ' . $src);
	$sig = [];
	$depth = 0;
	$in = false;

	foreach ($tokens as $t) {
		if ($t->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG])) {
			continue;
		}

		if ($t->text === '{') {
			$depth++;

			if ($depth === 1) {
				$in = true;

				continue;
			}
		}

		if ($t->text === '}') {
			$depth--;

			if ($depth === 0) {
				break;
			}
		}

		if ($in) {
			$sig[] = $t->text;
		}
	}

	return $sig;
}

if ($mode === 'callbacks') {
	foreach ($classes as $c) {
		if (!class_exists($c) && !trait_exists($c)) {
			continue;
		}

		$r = new ReflectionClass($c);

		foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
			if ($p->getDeclaringClass()->getName() !== $c) {
				continue;
			}

			$type = (string) $p->getType();
			$doc = (string) $p->getDocComment();

			if ($type === 'array' && (preg_match('/Closure|callable/', $doc) || preg_match('/^(on|before|after)[A-Z]/', $p->getName()))) {
				printf("%s::\$%s  %s:%d  %s\n", $c, $p->getName(), substr($r->getFileName(), strlen($root) + 1),
					$r->getStartLine(), trim(preg_replace('/\s+/', ' ', $doc)));
			}
		}
	}
} elseif ($mode === 'constants') {
	$r = new ReflectionClass('FastyBird\\Core\\Constants');

	foreach ($r->getReflectionConstants() as $k) {
		printf("%s\t%s\n", $k->getName(), var_export($k->getValue(), true));
	}
} elseif ($mode === 'accessors') {
	foreach ($classes as $c) {
		if (!class_exists($c) && !trait_exists($c)) {
			continue;
		}

		$r = new ReflectionClass($c);
		$getters = [];
		$setters = [];

		foreach ($r->getMethods() as $m) {
			if ($m->getDeclaringClass()->getName() !== $c || $m->isStatic() || $m->isAbstract()) {
				continue;
			}

			$b = bodyTokens($m);

			if (preg_match('/^(get|is|has)[A-Z]/', $m->getName()) && $m->getNumberOfParameters() === 0
				&& count($b) === 5 && $b[0] === 'return' && $b[1] === '$this' && $b[2] === '->' && $b[4] === ';') {
				$getters[$b[3]][] = $m;
			}

			if (preg_match('/^set[A-Z]/', $m->getName()) && $m->getNumberOfParameters() === 1) {
				$pn = '$' . $m->getParameters()[0]->getName();
				$fluent = $b === ['$this', '->', $b[2] ?? '', '=', $pn, ';', 'return', '$this', ';'];

				if ((count($b) === 6 && $b[0] === '$this' && $b[1] === '->' && $b[3] === '=' && $b[4] === $pn && $b[5] === ';') || $fluent) {
					$setters[$b[2]][] = [$m, $fluent];
				}
			}
		}

		foreach ($getters as $prop => $gs) {
			if (!isset($setters[$prop])) {
				continue;
			}

			foreach ($gs as $g) {
				foreach ($setters[$prop] as [$s, $fluent]) {
					$pr = $r->hasProperty($prop) ? $r->getProperty($prop) : null;
					printf("%s\t%s\t%s()\t%s%s()\t%s\t%s\t%s\t%s:%d\n", $c, $prop, $g->getName(),
						$s->isPublic() ? 'public ' : ($s->isProtected() ? 'protected ' : 'private '), $s->getName(),
						$fluent ? 'fluent' : 'void', $pr !== null ? (string) $pr->getType() : '?',
						$pr !== null && $pr->isReadOnly() ? 'readonly' : '',
						substr($r->getFileName(), strlen($root) + 1), $g->getStartLine());
				}
			}
		}
	}
} else {
	fwrite(STDERR, "usage: members.php callbacks|constants|accessors\n");
	exit(2);
}
