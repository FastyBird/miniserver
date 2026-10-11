<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;

/**
 * Shelly channel entity hydrator
 *
 * @template  T of Entities\Channels\Shelly
 * @extends HomeKitHydrators\Channels\Channel<T>
 */
abstract class Shelly extends HomeKitHydrators\Channels\Channel
{

}
