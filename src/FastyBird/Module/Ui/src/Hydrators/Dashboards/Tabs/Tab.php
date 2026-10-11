<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Dashboards\Tabs;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use Override;
use function is_scalar;

/**
 * Tab entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Dashboards\Tabs\Tab>
 */
final class Tab extends Hydrators\Hydrator
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

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Dashboards\Tabs\Tab::class;
	}

	protected function hydrateNameAttribute(Objects\StandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateCommentAttribute(Objects\StandardObject $attributes): string|null
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
