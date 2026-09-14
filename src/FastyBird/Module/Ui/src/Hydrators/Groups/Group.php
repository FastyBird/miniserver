<?php declare(strict_types = 1);

/**
 * Group.php
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

namespace FastyBird\Module\Ui\Hydrators\Groups;

use FastyBird\Library\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use IPub\JsonAPIDocument;
use function is_scalar;

/**
 * Group entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Groups\Group>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Group extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'identifier',
		'name',
		'comment',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Groups\Group::RELATIONSHIPS_WIDGETS,
	];

	public function getEntityName(): string
	{
		return Entities\Groups\Group::class;
	}

	protected function hydrateNameAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateCommentAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
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
