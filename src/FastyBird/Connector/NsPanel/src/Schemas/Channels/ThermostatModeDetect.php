<?php declare(strict_types = 1);

/**
 * ThermostatModeDetect.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           16.10.24
 */

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * NS Panel thermostat mode detect channel entity schema
 *
 * @extends Channel<Entities\Channels\ThermostatModeDetect>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThermostatModeDetect extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\ThermostatModeDetect::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\ThermostatModeDetect::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
