<?php declare(strict_types = 1);

/**
 * AllowedHrefNode.php
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

use Generator;
use Latte;
use Latte\Compiler;
use function sprintf;

class AllowedHrefNode extends Compiler\Nodes\StatementNode
{

	public Compiler\Nodes\Php\ExpressionNode $destination;

	public Compiler\Nodes\Php\Expression\ArrayNode $args;

	public Compiler\Nodes\Php\ModifierNode $modifier;

	public string $mode;

	/**
	 * @throws Latte\CompileException
	 */
	public static function create(Compiler\Tag $tag): static|null
	{
		$tag->outputMode = $tag::OutputKeepIndentation;
		$tag->expectArguments();

		// Check valid tag use...
		$tagName = $tag->htmlElement?->name ?? '';

		if ($tagName !== 'a') {
			throw new Latte\CompileException(
				sprintf('Invalid usage on tag <%s> - n:allowedHref macro can be used only on tag <a //>', $tagName),
				$tag->position,
			);
		}

		// Check existing attributes...
		foreach ($tag->htmlElement->attributes->children ?? [] as $child) {
			if (
				$child instanceof Compiler\Nodes\Html\AttributeNode
				&& $child->name instanceof Compiler\Nodes\TextNode
			) {
				if ($child->name->content === 'href') {
					throw new Latte\CompileException(
						sprintf(
							'Tag <%s> already has main location attribute href="", can not be used together with n:allowedHref macro',
							$tagName,
						),
						$tag->position,
					);
				}
			}
		}

		$node = new static();
		$node->destination = $tag->parser->parseUnquotedStringOrExpression();

		$tag->parser->stream->tryConsume(',');

		$node->args = $tag->parser->parseArguments();
		$node->modifier = $tag->parser->parseModifier();
		$node->modifier->escape = true;
		$node->modifier->check = false;
		$node->mode = $tag->name;

		return $node;
	}

	public function print(Compiler\PrintContext $context): string
	{
		if ($this->mode === 'allowedHref') {
			$context->beginEscape()->enterHtmlAttribute();

			$res = $context->format(
				<<<'XX'
						if ($this->global->_nLinkChecker->isAllowed(%node)) {
						echo ' href="'; echo %modify($this->global->uiControl->link(%node, %node?)) %line; echo '"';
						} else {
						echo ' href="javascript:void(0)"';
						}
					XX,
				$this->destination,
				$this->modifier,
				$this->destination,
				$this->args,
				$this->position,
			);

			$context->restoreEscape();

			return $res;
		}

		return $context->format(
			'if ($this->global->_nLinkChecker->isAllowed(%node)) {'
			. 'echo %modify('
			. ($this->mode === 'plink' ? '$this->global->uiPresenter' : '$this->global->uiControl')
			. '->link(%node, %node?)) %line;'
			. '} else {'
			. 'echo \' href="javascript:void(0)"\';'
			. '}',
			$this->destination,
			$this->modifier,
			$this->destination,
			$this->args,
			$this->position,
		);
	}

	public function &getIterator(): Generator
	{
		yield $this->destination;
		yield $this->args;
		yield $this->modifier;
	}

}
