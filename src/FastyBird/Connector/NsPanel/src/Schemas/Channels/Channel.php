<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel channel entity schema
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesSchemas\Channels\Channel<T>
 */
abstract class Channel extends DevicesSchemas\Channels\Channel
{

}
