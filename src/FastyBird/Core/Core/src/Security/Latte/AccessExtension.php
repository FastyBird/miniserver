<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Latte;

use FastyBird\Core\Security\Access;
use Latte;
use Override;

final class AccessExtension extends Latte\Extension
{

	public function __construct(
		private readonly Access\LinkChecker $linkChecker,
		private readonly Access\LatteChecker $latteChecker,
	)
	{
	}

	#[Override]
	public function getTags(): array
	{
		return [
			'ifAllowed' => Nodes\IfAllowedNode::create(...),
			'n:elseAllowed' => [Nodes\NElseAllowedNode::class, 'create'],
			'n:allowedHref' => Nodes\AllowedHrefNode::create(...),
		];
	}

	#[Override]
	public function getPasses(): array
	{
		return [
			'nElseAllowed' => [Nodes\NElseAllowedNode::class, 'processPass'],
		];
	}

	#[Override]
	public function getProviders(): array
	{
		return [
			'_nLinkChecker' => $this->linkChecker,
			'_nLatteChecker' => $this->latteChecker,
		];
	}

}
