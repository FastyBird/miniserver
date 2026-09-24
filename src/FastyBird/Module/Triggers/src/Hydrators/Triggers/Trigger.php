<?php declare(strict_types = 1);

/**
 * Trigger.php
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
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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

	protected function hydrateEnabledAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
