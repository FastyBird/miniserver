<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Latte\Nodes;

use Generator;
use Latte;
use Latte\Compiler;
use Override;
use TypeError;

final class IfAllowedNode extends Compiler\Nodes\StatementNode
{

	public Compiler\Nodes\Php\Expression\ArrayNode $args;

	public Compiler\Nodes\AreaNode $then;

	public Compiler\Nodes\AreaNode|null $else = null;

	public Compiler\Position|null $elseLine = null;

	public bool $capture = false;

	/**
	 * @throws Latte\CompileException
	 * @throws TypeError
	 */
	public static function create(Compiler\Tag $tag): Generator
	{
		$node = $tag->node = new static();
		$node->capture = !$tag->isNAttribute() && $tag->name === 'ifAllowed' && $tag->parser->isEnd();
		$node->position = $tag->position;

		if (!$node->capture) {
			$tag->parser->stream->tryConsume(',');
			$node->args = $tag->parser->parseArguments();
		}

		[$node->then, $nextTag] = yield ['elseAllowed', 'else'];

		if ($nextTag?->name === 'else' || $nextTag?->name === 'elseAllowed') {
			if ($nextTag->parser->stream->is('ifAllowed')) {
				throw new Latte\CompileException('Arguments are not allowed in {elseAllowed}', $nextTag->position);
			}

			$node->elseLine = $nextTag->position;

			[$node->else, $nextTag] = yield;
		}

		if ($node->capture) {
			$tag->parser->stream->tryConsume(',');
			$node->args = $nextTag->parser->parseArguments();
		}

		return $node;
	}

	public function print(Compiler\PrintContext $context): string
	{
		return $this->capture
			? $this->printCapturing($context)
			: $this->printCommon($context);
	}

	private function printCommon(Compiler\PrintContext $context): string
	{
		if ($this->else !== null) {
			return $context->format(
				($this->else instanceof self
					? "if (\$this->global->_nLatteChecker->isAllowed(%node)) %line { %node } else%node\n"
					: "if (\$this->global->_nLatteChecker->isAllowed(%node)) %line { %node } else %4.line { %3.node }\n"),
				$this->args,
				$this->position,
				$this->then,
				$this->else,
				$this->elseLine,
			);
		}

		return $context->format(
			"if (\$this->global->_nLatteChecker->isAllowed(%node)) %line { %node }\n",
			$this->args,
			$this->position,
			$this->then,
		);
	}

	private function printCapturing(Compiler\PrintContext $context): string
	{
		if ($this->else !== null) {
			return $context->format(
				<<<'XX'
					ob_start(fn() => '') %line;
					try {
						%node
						ob_start(fn() => '') %line;
						try {
							%node
						} finally {
							$ʟ_ifAllowedB = ob_get_clean();
						}
					} finally {
						$ʟ_ifAllowedA = ob_get_clean();
					}
					echo $this->global->_nLatteChecker->isAllowed(%node) ? $ʟ_ifAllowedA : $ʟ_ifAllowedB %0.line;


					XX,
				$this->position,
				$this->then,
				$this->elseLine,
				$this->else,
				$this->args,
			);
		}

		return $context->format(
			<<<'XX'
				ob_start(fn() => '') %line;
				try {
					%node
				} finally {
					$ʟ_ifAllowedA = ob_get_clean();
				}
				if $this->global->_nLatteChecker->isAllowed(%node) %0.line { echo $ʟ_ifAllowedA; }

				XX,
			$this->position,
			$this->then,
			$this->args,
		);
	}

	#[Override]
	public function &getIterator(): Generator
	{
		yield $this->args;
		yield $this->then;

		if ($this->else !== null) {
			yield $this->else;
		}
	}

}
