<?php declare(strict_types = 1);

namespace FastyBird\Core\Latte\SimpleAuth;

use FastyBird\Core\Security\SimpleAuth\Access;
use Latte;

class AccessExtension extends Latte\Extension
{

	public function __construct(
		private readonly Access\LinkChecker $linkChecker,
		private readonly Access\LatteChecker $latteChecker,
	)
	{
	}

	public function getTags(): array
	{
		return [
			'ifAllowed' => Nodes\IfAllowedNode::create(...),
			'n:elseAllowed' => [Nodes\NElseAllowedNode::class, 'create'],
			'n:allowedHref' => Nodes\AllowedHrefNode::create(...),
		];
	}

	public function getPasses(): array
	{
		return [
			'nElseAllowed' => [Nodes\NElseAllowedNode::class, 'processPass'],
		];
	}

	public function getProviders(): array
	{
		return [
			'_nLinkChecker' => $this->linkChecker,
			'_nLatteChecker' => $this->latteChecker,
		];
	}

}
