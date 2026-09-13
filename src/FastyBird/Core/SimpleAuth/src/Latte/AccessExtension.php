<?php declare(strict_types = 1);

/**
 * AccessExtension.php
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

namespace FastyBird\Core\SimpleAuth\Latte;

use FastyBird\Core\SimpleAuth\Access;
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
