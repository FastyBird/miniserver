<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;
use Override;

/**
 * Generic channel entity hydrator
 *
 * @template  T of Entities\Channels\Generic
 * @extends   Channel<T>
 */
final class Generic extends Channel
{

	/**
	 * @return class-string<Entities\Channels\Generic>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Generic::class;
	}

}
