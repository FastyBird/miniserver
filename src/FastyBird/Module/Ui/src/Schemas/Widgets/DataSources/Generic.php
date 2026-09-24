<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Module\Ui\Schemas\Widgets\DataSources;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;

/**
 * Generic data source entity schema
 *
 * @extends DataSource<Entities\Widgets\DataSources\Generic>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends DataSource
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::UI->value . '/data-source/' . Entities\Widgets\DataSources\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Widgets\DataSources\Generic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
