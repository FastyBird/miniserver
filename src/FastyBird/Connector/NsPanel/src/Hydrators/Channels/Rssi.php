<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel RSSI capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Rssi>
 */
final class Rssi extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Rssi::class;
	}

}
