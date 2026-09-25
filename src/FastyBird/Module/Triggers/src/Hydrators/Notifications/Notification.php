<?php declare(strict_types = 1);

/**
 * Notification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.04.20
 */

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
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Notification extends Hydrators\Hydrator
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Notifications\Notification::RELATIONSHIPS_TRIGGER,
	];

	protected function hydrateEnabledAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
