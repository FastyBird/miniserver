<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Api\Hydrators;

/**
 * @extends Hydrators\Hydrator<DummyCrudEntity>
 */
final class DummyCrudHydrator extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'identifier',
		'label',
		'secret',
	];

	public function getEntityName(): string
	{
		return DummyCrudEntity::class;
	}

}
