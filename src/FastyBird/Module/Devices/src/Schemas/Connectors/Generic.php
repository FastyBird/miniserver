<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Schemas\Connectors;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Generic connector entity schema
 *
 * @extends Connector<Entities\Connectors\Generic>
 */
final class Generic extends Connector
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::DEVICES->value . '/connector/' . Entities\Connectors\Generic::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Connectors\Generic::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
