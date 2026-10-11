<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;

/**
 * Light bulb channel entity hydrator
 *
 * @template  T of Entities\Channels\LightBulb
 * @extends   Channel<T>
 */
final class LightBulb extends Channel
{

	/**
	 * @return class-string<Entities\Channels\LightBulb>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\LightBulb::class;
	}

}
