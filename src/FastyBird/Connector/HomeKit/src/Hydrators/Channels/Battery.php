<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;

/**
 * Battery channel entity hydrator
 *
 * @template  T of Entities\Channels\Battery
 * @extends   Channel<T>
 */
class Battery extends Channel
{

	/**
	 * @return class-string<Entities\Channels\Battery>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\Battery::class;
	}

}
