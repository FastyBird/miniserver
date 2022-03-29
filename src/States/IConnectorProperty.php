<?php declare(strict_types = 1);

/**
 * IConnectorProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.2.0
 *
 * @date           29.03.22
 */

namespace FastyBird\MiniServer\States;

use FastyBird\DevicesModule\States as DevicesModuleStates;

/**
 * Connector property state entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConnectorProperty extends IProperty, DevicesModuleStates\IConnectorProperty
{

}
