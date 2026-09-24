<?php declare(strict_types = 1);

/**
 * Condition.php
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

namespace FastyBird\Module\Triggers\Hydrators\Conditions;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators as ApiHydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Hydrators as TriggersHydrators;
use FastyBird\Module\Triggers\Schemas;
use function is_scalar;

/**
 * Condition entity hydrator
 *
 * @template T of Entities\Conditions\Condition
 * @extends  ApiHydrators\Hydrator<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Condition extends ApiHydrators\Hydrator
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Conditions\Condition::RELATIONSHIPS_TRIGGER,
	];

	protected function hydrateEnabledAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
