<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;

/**
 * Viera channel entity hydrator
 *
 * @template  T of Entities\Channels\Viera
 * @extends HomeKitHydrators\Channels\Channel<T>
 */
abstract class Viera extends HomeKitHydrators\Channels\Channel
{

}
