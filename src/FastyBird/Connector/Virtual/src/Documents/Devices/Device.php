<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Documents\Devices;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\MappedSuperclass]
abstract class Device extends DevicesDocuments\Devices\Device
{

}
