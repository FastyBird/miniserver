<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use Override;

/**
 * Color lightbulb channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Lightbulb>
 */
final class Lightbulb extends Shelly
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Lightbulb::class;
	}

}
