<?php declare(strict_types = 1);

/**
 * SubDevice.php
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
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * NS Panel sub-device entity schema
 *
 * @extends Device<Entities\Devices\SubDevice>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SubDevice extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::NS_PANEL->value . '/device/' . Entities\Devices\SubDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\SubDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
