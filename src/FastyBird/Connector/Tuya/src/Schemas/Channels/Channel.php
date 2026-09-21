<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Tuya\Schemas\Channels;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Tuya channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\Channels\Channel>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::TUYA->value . '/channel/' . Entities\Channels\Channel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Channel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
