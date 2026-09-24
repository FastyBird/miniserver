<?php declare(strict_types = 1);

/**
 * Fault.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           06.10.24
 */

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel fault channel entity schema
 *
 * @extends Channel<Entities\Channels\Fault>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Fault extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Fault::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Fault::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
