<?php declare(strict_types = 1);

/**
 * Tab.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Hydrators\Dashboards\Tabs;

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use function is_scalar;

/**
 * Tab entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Dashboards\Tabs\Tab>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Tab extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'identifier',
		'name',
		'comment',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Dashboards\Tabs\Tab::RELATIONSHIPS_DASHBOARD,
		Schemas\Dashboards\Tabs\Tab::RELATIONSHIPS_WIDGETS,
	];

	public function getEntityName(): string
	{
		return Entities\Dashboards\Tabs\Tab::class;
	}

	protected function hydrateNameAttribute(JsonApi\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateCommentAttribute(JsonApi\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('comment'))
			|| (string) $attributes->get('comment') === ''
		) {
			return null;
		}

		return (string) $attributes->get('comment');
	}

}
