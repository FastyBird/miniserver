<?php declare(strict_types = 1);

/**
 * IChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           12.01.22
 */

namespace FastyBird\MiniServer\States;

use FastyBird\DevicesModule\States as DevicesModuleStates;

/**
 * Channel property state entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IChannelProperty extends IProperty, DevicesModuleStates\IChannelProperty
{

}
