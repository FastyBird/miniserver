<?php declare(strict_types = 1);

/**
 * E5.1 census (#633) -- the reference index every other census script reads.
 *
 * Parses every tracked PHP file of the repository (git ls-files, minus vendor/) with the
 * vendored nikic/php-parser and its NameResolver, and writes one JSON file:
 *
 *   decls  FQCN => {kind, file, line, final, abstract, readonly, extends[], implements[],
 *                   traits[], anonymous:false}; anonymous classes are recorded under
 *                   "class@anonymous:<file>:<line>" with anonymous:true
 *   refs   list of [fqcn, file, line, how] -- every resolved class-like name in code
 *          (how = implements|extends|new|static|const|instanceof|type|catch|attribute|
 *          trait|other) and every class-like word in the type position of a docblock tag
 *          (how = doc), resolved through the file's own imports (NameContext)
 *   strings list of [literal, file, line] for every string literal naming FastyBird\Core\...
 *
 * Usage (application image, repository at /app, read-only is fine):
 *   (host)  git ls-files -- '*.php' > /tmp/e633/files-php.txt
 *   (image) php tools/census/e5/refs.php /e633/files-php.txt > /e633/refs.json
 */

require __DIR__ . '/../../../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

$root = realpath(__DIR__ . '/../../..');
chdir($root);

// argv[1]: a file list (one repository-relative path per line) made on the host with
// `git ls-files -- '*.php'`; a worktree's .git does not resolve inside the container
$files = [];
$rc = 1;

if (isset($argv[1])) {
	$files = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$rc = 0;
} else {
	exec('git ls-files -- "*.php"', $files, $rc);
}

if ($rc !== 0) {
	// The image may not have git or the mount may not be a work tree: fall back to find
	$files = [];
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS));

	foreach ($it as $f) {
		$p = substr($f->getPathname(), 2);

		if (str_ends_with($p, '.php') && !str_starts_with($p, 'vendor/') && !str_starts_with($p, 'var/')
			&& !str_starts_with($p, 'node_modules/') && !str_contains($p, '/node_modules/')) {
			$files[] = $p;
		}
	}
}

$files = array_values(array_filter($files, static fn ($f) => !str_starts_with($f, 'vendor/')));
sort($files);

$parser = (new ParserFactory())->createForHostVersion();

$out = ['decls' => [], 'refs' => [], 'strings' => [], 'files' => count($files), 'errors' => []];

final class Collector extends NodeVisitorAbstract
{

	public string $file = '';

	public array $out;

	private array $parents = [];

	public static function fq(Node\Name $n): string
	{
		$r = $n->getAttribute('resolvedName');

		return $r instanceof Node\Name ? $r->toString() : $n->toString();
	}

	public function __construct(public NameResolver $resolver, array &$out)
	{
		$this->out = &$out;
	}

	private function add(string $fqcn, int $line, string $how): void
	{
		$fqcn = ltrim($fqcn, '\\');

		if ($fqcn === '' || in_array(strtolower($fqcn), ['self', 'static', 'parent'], true)) {
			return;
		}

		$this->out['refs'][] = [$fqcn, $this->file, $line, $how];
	}

	public function enterNode(Node $node)
	{
		$parent = end($this->parents) ?: null;
		$this->parents[] = $node;

		if ($node instanceof Node\Stmt\ClassLike) {
			$anon = $node instanceof Node\Stmt\Class_ && $node->name === null;
			$name = $anon
				? 'class@anonymous:' . $this->file . ':' . $node->getStartLine()
				: $node->namespacedName->toString();
			$kind = match (true) {
				$node instanceof Node\Stmt\Interface_ => 'interface',
				$node instanceof Node\Stmt\Trait_ => 'trait',
				$node instanceof Node\Stmt\Enum_ => 'enum',
				default => 'class',
			};
			$extends = [];
			$implements = [];

			if ($node instanceof Node\Stmt\Class_) {
				$extends = $node->extends !== null ? [Collector::fq($node->extends)] : [];
				$implements = array_map(static fn ($n) => Collector::fq($n), $node->implements);
			} elseif ($node instanceof Node\Stmt\Interface_) {
				$extends = array_map(static fn ($n) => Collector::fq($n), $node->extends);
			} elseif ($node instanceof Node\Stmt\Enum_) {
				$implements = array_map(static fn ($n) => Collector::fq($n), $node->implements);
			}

			$traits = [];

			foreach ($node->stmts as $s) {
				if ($s instanceof Node\Stmt\TraitUse) {
					foreach ($s->traits as $t) {
						$traits[] = Collector::fq($t);
					}
				}
			}

			$this->out['decls'][$name] = [
				'kind' => $kind,
				'file' => $this->file,
				'line' => $node->getStartLine(),
				'final' => $node instanceof Node\Stmt\Class_ && $node->isFinal(),
				'abstract' => $node instanceof Node\Stmt\Class_ && $node->isAbstract(),
				'readonly' => $node instanceof Node\Stmt\Class_ && $node->isReadonly(),
				'extends' => $extends,
				'implements' => $implements,
				'traits' => $traits,
				'anonymous' => $anon,
			];
		}

		if ($node instanceof Node\Name && !$parent instanceof Node\Stmt\Namespace_
			&& !$parent instanceof Node\Stmt\UseUse && !$parent instanceof Node\UseItem
			&& !$parent instanceof Node\Expr\FuncCall && !$parent instanceof Node\Expr\ConstFetch
			&& !$parent instanceof Node\Stmt\GroupUse) {
			$how = match (true) {
				$parent instanceof Node\Stmt\Class_ && in_array($node, $parent->implements, true) => 'implements',
				$parent instanceof Node\Stmt\Enum_ => 'implements',
				$parent instanceof Node\Stmt\Class_ => 'extends',
				$parent instanceof Node\Stmt\Interface_ => 'extends',
				$parent instanceof Node\Expr\New_ => 'new',
				$parent instanceof Node\Expr\StaticCall || $parent instanceof Node\Expr\StaticPropertyFetch => 'static',
				$parent instanceof Node\Expr\ClassConstFetch => 'const',
				$parent instanceof Node\Expr\Instanceof_ => 'instanceof',
				$parent instanceof Node\Stmt\Catch_ => 'catch',
				$parent instanceof Node\Attribute => 'attribute',
				$parent instanceof Node\Stmt\TraitUse || $parent instanceof Node\Stmt\TraitUseAdaptation => 'trait',
				$parent instanceof Node\Param || $parent instanceof Node\FunctionLike
					|| $parent instanceof Node\Stmt\Property || $parent instanceof Node\NullableType
					|| $parent instanceof Node\UnionType || $parent instanceof Node\IntersectionType
					|| $parent instanceof Node\Stmt\ClassConst => 'type',
				default => 'other',
			};

			$resolved = $node->getAttribute('resolvedName');
			$name = $resolved instanceof Node\Name ? $resolved->toString() : $node->toString();

			if (!in_array(strtolower($name), ['null', 'true', 'false', 'int', 'string', 'bool', 'float', 'array',
				'mixed', 'void', 'never', 'object', 'iterable', 'callable'], true)) {
				$this->add($name, $node->getStartLine(), $how);
			}
		}

		if ($node instanceof Node\Scalar\String_ && str_contains($node->value, 'FastyBird\\Core\\')) {
			$this->out['strings'][] = [$node->value, $this->file, $node->getStartLine()];
		}

		$doc = $node->getDocComment();

		if ($doc !== null && !$node instanceof Node\Stmt\Namespace_) {
			$this->docRefs($doc->getText(), $doc->getStartLine());
		}

		foreach ($node->getComments() as $c) {
			if ($c instanceof PhpParser\Comment\Doc && $c !== $doc) {
				$this->docRefs($c->getText(), $c->getStartLine());
			}
		}

		return null;
	}

	public function leaveNode(Node $node)
	{
		array_pop($this->parents);

		return null;
	}

	private function docRefs(string $text, int $startLine): void
	{
		static $seen = [];
		$key = $this->file . ':' . $startLine . ':' . md5($text);

		if (isset($seen[$key])) {
			return;
		}

		$seen[$key] = true;
		$tags = '(?:var|param|return|throws|property|property-read|property-write|method|template|template-covariant|'
			. 'template-contravariant|extends|implements|mixin|see|phpstan-[a-z-]+|psalm-[a-z-]+)';

		foreach (explode("\n", $text) as $i => $line) {
			if (!preg_match('/@' . $tags . '\s+(.*)$/', $line, $m)) {
				continue;
			}

			// The type expression: up to the first variable or the first space outside <>, {}, ()
			$expr = $m[1];
			$depth = 0;
			$type = '';

			for ($j = 0; $j < strlen($expr); $j++) {
				$ch = $expr[$j];

				if ($ch === '<' || $ch === '{' || $ch === '(') {
					$depth++;
				} elseif ($ch === '>' || $ch === '}' || $ch === ')') {
					$depth--;
				} elseif (($ch === ' ' || $ch === "\t") && $depth <= 0) {
					break;
				}

				$type .= $ch;
			}

			if (str_starts_with($m[0], '@template') || str_starts_with($m[0], '@phpstan-template')) {
				// "@template T of Foo": the bound is the type
				if (preg_match('/\bof\s+(\S+)/', $expr, $mm)) {
					$type = $mm[1];
				} else {
					continue;
				}
			}

			preg_match_all('/\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*/', $type, $words);

			foreach ($words[0] as $w) {
				if (preg_match('/^(array|list|int|string|bool|float|mixed|void|never|null|true|false|object|iterable|'
					. 'callable|self|static|parent|non-empty-string|class-string|positive-int|negative-int|key-of|'
					. 'value-of|resource|scalar|numeric|array-key|non-empty-array|non-empty-list|Closure|callable-string|'
					. 'int-mask|int-mask-of|literal-string|non-falsy-string|numeric-string|lowercase-string|of|'
					. 'stdClass|T|TKey|TValue|TEntity|TDocument|TReturn|TObject|TItem|TCollection)$/i', ltrim($w, '\\'))) {
					if (ltrim($w, '\\') !== 'Closure' && ltrim($w, '\\') !== 'stdClass') {
						continue;
					}
				}

				$name = str_starts_with($w, '\\')
					? substr($w, 1)
					: $this->resolver->getNameContext()->getResolvedClassName(new Node\Name($w))->toString();
				$this->add($name, $startLine + $i, 'doc');
			}
		}
	}

}

$resolver = new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]);
$collector = new Collector($resolver, $out);
$traverser = new NodeTraverser();
$traverser->addVisitor($resolver);
$traverser->addVisitor($collector);

foreach ($files as $file) {
	$code = @file_get_contents($file);

	if ($code === false) {
		continue;
	}

	try {
		$ast = $parser->parse($code);
	} catch (Throwable $e) {
		$out['errors'][] = [$file, $e->getMessage()];

		continue;
	}

	$collector->file = $file;
	$traverser->traverse($ast);
}

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
