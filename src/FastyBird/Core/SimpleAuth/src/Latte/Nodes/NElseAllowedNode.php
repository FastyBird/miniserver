<?php declare(strict_types = 1);

/**
 * NElseAllowedNode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Latte
 * @since          0.1.0
 *
 * @date           26.07.24
 */

namespace FastyBird\Core\SimpleAuth\Latte\Nodes;

use FastyBird\Core\SimpleAuth\Exceptions;
use Generator;
use Latte;
use Latte\Compiler;
use function array_splice;
use function count;
use function trim;

final class NElseAllowedNode extends Compiler\Nodes\StatementNode
{

	public Compiler\Nodes\AreaNode $content;

	public static function create(Compiler\Tag $tag): Generator
	{
		$node = $tag->node = new static();

		[$node->content] = yield;

		return $node;
	}

	/**
	 * @throws Exceptions\Logical
	 */
	public function print(Compiler\PrintContext $context): string
	{
		throw new Exceptions\Logical('Cannot directly print');
	}

	public static function processPass(Compiler\Node $node): void
	{
		(new Compiler\NodeTraverser())->traverse($node, static function (Compiler\Node $node): void {
			if ($node instanceof Compiler\Nodes\FragmentNode) {
				for ($i = count($node->children) - 1; $i >= 0; $i--) {
					$nElse = $node->children[$i];

					if (!$nElse instanceof self) {
						continue;
					}

					array_splice($node->children, $i, 1);

					$prev = $node->children[--$i] ?? null;

					if ($prev instanceof Compiler\Nodes\TextNode && trim($prev->content) === '') {
						array_splice($node->children, $i, 1);
						$prev = $node->children[--$i] ?? null;
					}

					if ($prev instanceof IfAllowedNode) {
						if ($prev->else !== null) {
							throw new Latte\CompileException('Multiple "elseAllowed" found.', $nElse->position);
						}

						$prev->else = $nElse->content;
					} else {
						throw new Latte\CompileException(
							'n:elseAllowed must be immediately after n:ifAllowed',
							$nElse->position,
						);
					}
				}
			} elseif ($node instanceof self) {
				throw new Latte\CompileException(
					'n:elseAllowed must be immediately after n:ifAllowed',
					$node->position,
				);
			}
		});
	}

	public function &getIterator(): Generator
	{
		yield $this->content;
	}

}
