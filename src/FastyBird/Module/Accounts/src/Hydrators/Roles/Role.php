<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Roles;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Schemas;
use Override;
use function is_scalar;

/**
 * Role entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Roles\Role>
 */
final class Role extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'comment',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Roles\Role::RELATIONSHIPS_PARENT,
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Roles\Role::class;
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
