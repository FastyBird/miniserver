<?php declare(strict_types = 1);

/**
 * ThirdPartyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel third-party device entity schema
 *
 * @extends Device<Entities\Devices\ThirdPartyDevice>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThirdPartyDevice extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/device/' . Entities\Devices\ThirdPartyDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\ThirdPartyDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
