<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets\DataSources;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;

/**
 * Generic data source entity schema
 *
 * @extends DataSource<Entities\Widgets\DataSources\Generic>
 */
final class Generic extends DataSource
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/data-source/' . Entities\Widgets\DataSources\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Widgets\DataSources\Generic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
