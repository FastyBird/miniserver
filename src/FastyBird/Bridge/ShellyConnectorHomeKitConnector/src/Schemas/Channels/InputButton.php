<?php declare(strict_types = 1);

/**
 * InputButton.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * Input button type channel entity schema
 *
 * @template T of Entities\Channels\InputButton
 * @extends  Shelly<T>
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class InputButton extends Shelly
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\InputButton::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\InputButton::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
