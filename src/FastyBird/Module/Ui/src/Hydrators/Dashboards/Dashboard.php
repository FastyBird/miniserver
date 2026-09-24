<?php declare(strict_types = 1);

/**
 * Dashboard.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Dashboards;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators as ApiHydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use function is_scalar;

/**
 * Dashboard entity hydrator
 *
 * @extends ApiHydrators\Hydrator<Entities\Dashboards\Dashboard>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Dashboard extends ApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'identifier',
		'name',
		'comment',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Dashboards\Dashboard::RELATIONSHIPS_TABS,
	];

	public function getEntityName(): string
	{
		return Entities\Dashboards\Dashboard::class;
	}

	protected function hydrateNameAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateCommentAttribute(Objects\IStandardObject $attributes): string|null
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
