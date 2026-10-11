<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Notifications;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;
use function is_scalar;

/**
 * Notification entity hydrator
 *
 * @template T of Entities\Notifications\Notification
 * @extends  Hydrators\Hydrator<T>
 */
abstract class Notification extends Hydrators\Hydrator
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Notifications\Notification::RELATIONSHIPS_TRIGGER,
	];

	protected function hydrateEnabledAttribute(Objects\StandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
