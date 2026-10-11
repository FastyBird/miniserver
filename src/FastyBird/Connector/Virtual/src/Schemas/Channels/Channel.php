<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Schemas\Channels;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual channel entity schema
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesSchemas\Channels\Channel<T>
 */
abstract class Channel extends DevicesSchemas\Channels\Channel
{

}
