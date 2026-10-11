<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Actions;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators as ApiHydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;
use function is_scalar;

/**
 * Action entity hydrator
 *
 * @template T of Entities\Actions\Action
 * @extends  ApiHydrators\Hydrator<T>
 */
abstract class Action extends ApiHydrators\Hydrator
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Actions\Action::RELATIONSHIPS_TRIGGER,
	];

	protected function hydrateEnabledAttribute(Objects\StandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
