<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Conditions;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators as ApiHydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;
use function is_scalar;

/**
 * Condition entity hydrator
 *
 * @template T of Entities\Conditions\Condition
 * @extends  ApiHydrators\Hydrator<T>
 */
abstract class Condition extends ApiHydrators\Hydrator
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Conditions\Condition::RELATIONSHIPS_TRIGGER,
	];

	protected function hydrateEnabledAttribute(Objects\StandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
