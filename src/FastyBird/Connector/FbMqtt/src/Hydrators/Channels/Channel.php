<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Hydrators\Channels;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * FastyBird MQTT channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\Channels\Channel>
 */
final class Channel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Channel::class;
	}

}
