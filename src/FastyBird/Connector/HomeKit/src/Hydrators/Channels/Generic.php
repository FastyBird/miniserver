<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;

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
	public function getEntityName(): string
	{
		return Entities\Channels\Generic::class;
	}

}
