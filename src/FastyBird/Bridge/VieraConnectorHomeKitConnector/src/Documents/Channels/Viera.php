<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\MappedSuperclass()]
abstract class Viera extends HomeKitDocuments\Channels\Channel
{

}
