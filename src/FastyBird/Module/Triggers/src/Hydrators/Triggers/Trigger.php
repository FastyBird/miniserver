<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;
use function is_scalar;

/**
 * Trigger entity hydrator
 *
 * @template T of Entities\Triggers\Trigger
 * @extends  Hydrators\Hydrator<T>
 */
abstract class Trigger extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'name',
		'comment',
		'enabled',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Triggers\Trigger::RELATIONSHIPS_ACTIONS,
		Schemas\Triggers\Trigger::RELATIONSHIPS_NOTIFICATIONS,
	];

	protected function hydrateEnabledAttribute(Objects\StandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
