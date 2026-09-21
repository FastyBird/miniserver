<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Connector\Virtual\Documents\Devices;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[ApplicationDocuments\Mapping\MappedSuperclass]
abstract class Device extends DevicesDocuments\Devices\Device
{

}
