<?php declare(strict_types = 1);

/**
 * E5.1 census (#633), table T8: getNow() results mutated in place.
 *
 *   php tools/census/e5/t8.php /e633/files-php.txt > /e633/t8.txt
 *
 * Parsed with nikic/php-parser. Within every function, method and closure body (each its own
 * scope; arrow functions and closures inherit nothing), a variable or property ($x, $this->x)
 * is "clock-held" from an assignment whose right side is a ->getNow() call, possibly wrapped
 * in clone/parentheses. Reported:
 *
 *   MUTATED   a clock-held target receiving modify/add/sub/setDate/setISODate/setTime/
 *             setTimestamp/setTimezone/setMicrosecond as an expression statement (result
 *             discarded) -- the in-place mutation that is a no-op on DateTimeImmutable
 *   CHAIN     ->getNow()->modify(...) etc. as an expression statement (result discarded)
 *   PASSED    a clock-held variable passed by reference-capable position is not tracked; the
 *             summary counts every ->getNow() call and every clock-held assignment so the
 *             reader can see the denominator
 */

require __DIR__ . '/../../../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

chdir(__DIR__ . '/../../..');
$files = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$parser = (new ParserFactory())->createForHostVersion();
$finder = new NodeFinder();
$pp = new Standard();
$mutators = ['modify', 'add', 'sub', 'setDate', 'setISODate', 'setTime', 'setTimestamp', 'setTimezone', 'setMicrosecond'];

$stats = ['getNow' => 0, 'held' => 0, 'files' => []];

$isGetNow = static function (Node $e) use (&$isGetNow): bool {
	while ($e instanceof Node\Expr\Clone_) {
		$e = $e->expr;
	}

	return ($e instanceof Node\Expr\MethodCall || $e instanceof Node\Expr\NullsafeMethodCall)
		&& $e->name instanceof Node\Identifier && $e->name->toString() === 'getNow';
};

foreach ($files as $file) {
	if (str_starts_with($file, 'vendor/') || str_starts_with($file, 'tools/')) {
		continue;
	}

	$code = (string) file_get_contents($file);

	if (!str_contains($code, 'getNow')) {
		continue;
	}

	$ast = $parser->parse($code);
	$calls = $finder->find($ast, static fn (Node $n) => ($n instanceof Node\Expr\MethodCall || $n instanceof Node\Expr\NullsafeMethodCall)
		&& $n->name instanceof Node\Identifier && $n->name->toString() === 'getNow');
	$stats['getNow'] += count($calls);

	if ($calls !== []) {
		$stats['files'][$file] = count($calls);
	}

	$scopes = $finder->find($ast, static fn (Node $n) => $n instanceof Node\FunctionLike && $n->getStmts() !== null);

	foreach ($scopes as $scope) {
		// statements of this scope only, not of nested closures
		$stmtsOwn = [];
		$walk = static function (array $nodes) use (&$walk, &$stmtsOwn): void {
			foreach ($nodes as $n) {
				if (!$n instanceof Node) {
					continue;
				}

				if ($n instanceof Node\FunctionLike) {
					continue;
				}

				$stmtsOwn[] = $n;

				foreach ($n->getSubNodeNames() as $sub) {
					$v = $n->$sub;

					if ($v instanceof Node) {
						$walk([$v]);
					} elseif (is_array($v)) {
						$walk($v);
					}
				}
			}
		};
		$walk($scope->getStmts());
		$held = [];

		foreach ($stmtsOwn as $n) {
			if ($n instanceof Node\Expr\Assign && $isGetNow($n->expr)) {
				$key = $pp->prettyPrintExpr($n->var);
				$held[$key] = $n->getStartLine();
				$stats['held']++;
				printf("HELD     %s:%d %s = %s\n", $file, $n->getStartLine(), $key, $pp->prettyPrintExpr($n->expr));
			}
		}

		foreach ($stmtsOwn as $n) {
			if (!$n instanceof Node\Stmt\Expression) {
				continue;
			}

			$e = $n->expr;

			if (($e instanceof Node\Expr\MethodCall || $e instanceof Node\Expr\NullsafeMethodCall)
				&& $e->name instanceof Node\Identifier && in_array($e->name->toString(), $mutators, true)) {
				if ($isGetNow($e->var)) {
					printf("CHAIN    %s:%d %s\n", $file, $n->getStartLine(), $pp->prettyPrintExpr($e));
				}

				$key = $pp->prettyPrintExpr($e->var);

				if (isset($held[$key])) {
					printf("MUTATED  %s:%d %s (held since line %d)\n", $file, $n->getStartLine(), $pp->prettyPrintExpr($e), $held[$key]);
				}
			}
		}
	}
}

printf("SUMMARY  %d ->getNow() calls in %d files; %d clock-held assignments\n", $stats['getNow'], count($stats['files']), $stats['held']);
