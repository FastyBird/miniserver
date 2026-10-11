<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Schemas\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * HomeKit channel entity schema
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesSchemas\Channels\Channel<T>
 */
abstract class Channel extends DevicesSchemas\Channels\Channel
{

}
