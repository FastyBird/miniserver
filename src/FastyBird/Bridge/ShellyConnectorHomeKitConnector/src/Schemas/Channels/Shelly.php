<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;

/**
 * Shelly channel entity schema
 *
 * @template T of Entities\Channels\Shelly
 * @extends  HomeKitSchemas\Channels\Channel<T>
 */
abstract class Shelly extends HomeKitSchemas\Channels\Channel
{

}
