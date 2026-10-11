<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;

/**
 * Viera channel entity schema
 *
 * @template T of Entities\Channels\Viera
 * @extends  HomeKitSchemas\Channels\Channel<T>
 */
abstract class Viera extends HomeKitSchemas\Channels\Channel
{

}
