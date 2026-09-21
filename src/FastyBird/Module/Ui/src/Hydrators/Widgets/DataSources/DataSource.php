<?php declare(strict_types = 1);

/**
 * DataSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           27.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\DataSources;

use FastyBird\Core\Persistence\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;

/**
 * Data source entity hydrator
 *
 * @template  T of Entities\Widgets\DataSources\DataSource
 * @extends   JsonApiHydrators\Hydrator<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class DataSource extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'params',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\DataSources\DataSource::RELATIONSHIPS_WIDGET,
	];

}
