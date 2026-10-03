<?php declare(strict_types = 1);

/**
 * E5.1 census (#633), table T5: every fetch of a class constant of the given classes, parsed
 * with nikic/php-parser; the class name is resolved through the file's imports (NameResolver),
 * so an aliased import is counted by FQCN. `self::`/`static::` inside the class itself are
 * resolved to it as well.
 *
 *   php tools/census/e5/consts.php /e633/files-php.txt 'FastyBird\Core\Constants' > /e633/consts.txt
 *
 * Output: one line per fetch: <class>::<CONST>\t<file>:<line>
 */

require __DIR__ . '/../../../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

chdir(__DIR__ . '/../../..');
$files = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$targets = array_flip(array_slice($argv, 2));
$parser = (new ParserFactory())->createForHostVersion();

$visitor = new class ($targets) extends NodeVisitorAbstract {

	public string $file = '';

	private string|null $class = null;

	public function __construct(private array $targets)
	{
	}

	public function enterNode(Node $node)
	{
		if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
			$this->class = $node->namespacedName->toString();
		}

		if ($node instanceof Node\Expr\ClassConstFetch && $node->class instanceof Node\Name
			&& $node->name instanceof Node\Identifier && $node->name->toString() !== 'class') {
			$name = $node->class->toString();

			if (in_array(strtolower($name), ['self', 'static'], true)) {
				$name = (string) $this->class;
			}

			if (isset($this->targets[$name])) {
				printf("%s::%s\t%s:%d\n", $name, $node->name->toString(), $this->file, $node->getStartLine());
			}
		}

		return null;
	}

};

$traverser = new NodeTraverser();
$traverser->addVisitor(new NameResolver());
$traverser->addVisitor($visitor);

foreach ($files as $file) {
	try {
		$ast = $parser->parse((string) file_get_contents($file));
	} catch (Throwable $e) {
		fwrite(STDERR, "parse error $file\n");

		continue;
	}

	$visitor->file = $file;
	$traverser->traverse($ast);
}
