<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Hydrators\Channels;

use FastyBird\Connector\HomeKit\Entities;
use Override;

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
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\LightBulb::class;
	}

}
