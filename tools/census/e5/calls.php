<?php declare(strict_types = 1);

/**
 * E5.1 census (#633): every method call, by method name, parsed with nikic/php-parser.
 *
 *   php tools/census/e5/calls.php /e633/files-php.txt <name>[,<name>...] > /e633/calls-<x>.txt
 *
 * Prints one line per call: file:line <receiver source> -> <name>(<argc>) [chain], where
 * [chain] names the method called directly on the result (e.g. getNow()->modify()), and
 * "stmt" when the call's value is discarded (an expression statement). The receiver is printed
 * as source text, so a reader (or a follow-up filter) can decide the receiver's type.
 */

require __DIR__ . '/../../../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

chdir(__DIR__ . '/../../..');
$files = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// names: comma-separated, or @<file> holding them comma- or newline-separated
$names = array_flip(array_filter(preg_split('/[,\s]+/', str_starts_with($argv[2], '@')
	? (string) file_get_contents(substr($argv[2], 1))
	: $argv[2])));
$parser = (new ParserFactory())->createForHostVersion();
$printer = new Standard();

$visitor = new class ($names, $printer) extends NodeVisitorAbstract {

	public string $file = '';

	public function __construct(private array $names, private Standard $printer)
	{
	}

	public function enterNode(Node $node)
	{
		if (($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall
			|| $node instanceof Node\Expr\StaticCall) && $node->name instanceof Node\Identifier
			&& isset($this->names[$node->name->toString()])) {
			$recv = $node instanceof Node\Expr\StaticCall ? $node->class : $node->var;
			$recvText = $recv instanceof Node\Name ? $recv->toString() : $this->printer->prettyPrintExpr($recv);
			$parent = $node->getAttribute('parent');
			$chain = '';

			if (($parent instanceof Node\Expr\MethodCall || $parent instanceof Node\Expr\NullsafeMethodCall)
				&& $parent->var === $node && $parent->name instanceof Node\Identifier) {
				$chain = ' chain=' . $parent->name->toString();
			}

			$ctx = $parent instanceof Node\Stmt\Expression ? ' stmt'
				: ($parent instanceof Node\Expr\Assign ? ' assigned-to=' . $this->printer->prettyPrintExpr($parent->var) : '');
			printf("%s:%d %s -> %s(%d)%s%s\n", $this->file, $node->getStartLine(), str_replace("\n", ' ', $recvText),
				$node->name->toString(), count($node->args), $chain, $ctx);
		}

		return null;
	}

};

$traverser = new NodeTraverser();
$traverser->addVisitor(new ParentConnectingVisitor());
$traverser->addVisitor($visitor);

foreach ($files as $file) {
	if (str_starts_with($file, 'vendor/')) {
		continue;
	}

	try {
		$ast = $parser->parse((string) file_get_contents($file));
	} catch (Throwable $e) {
		fwrite(STDERR, "parse error $file: {$e->getMessage()}\n");

		continue;
	}

	$visitor->file = $file;
	$traverser->traverse($ast);
}
