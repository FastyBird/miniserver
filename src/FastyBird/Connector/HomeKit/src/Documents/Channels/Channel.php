<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Documents\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Channel::class)]
abstract class Channel extends DevicesDocuments\Channels\Channel
{

}
