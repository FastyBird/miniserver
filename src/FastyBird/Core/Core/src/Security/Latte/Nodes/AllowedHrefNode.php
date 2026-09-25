<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Latte\Nodes;

use Generator;
use Latte;
use Latte\Compiler;
use Override;
use function sprintf;

final class AllowedHrefNode extends Compiler\Nodes\StatementNode
{

	public Compiler\Nodes\Php\ExpressionNode $destination;

	public Compiler\Nodes\Php\Expression\ArrayNode $args;

	public Compiler\Nodes\Php\ModifierNode $modifier;

	public string $mode;

	/**
	 * @throws Latte\CompileException
	 */
	public static function create(Compiler\Tag $tag): static
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

	#[Override]
	public function &getIterator(): Generator
	{
		yield $this->destination;
		yield $this->args;
		yield $this->modifier;
	}

}
