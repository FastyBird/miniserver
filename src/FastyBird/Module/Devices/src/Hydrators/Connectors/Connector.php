<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           16.04.21
 */

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Devices\Entities;
use function boolval;
use function is_scalar;

/**
 * Connector entity hydrator
 *
 * @template  T of Entities\Connectors\Connector
 * @extends   Hydrators\Hydrator<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Connector extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes
		= [
			'category',
			'identifier',
			'name',
			'comment',
			'enabled',
		];

	protected function hydrateNameAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateCommentAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('comment'))
			|| (string) $attributes->get('comment') === ''
		) {
			return null;
		}

		return (string) $attributes->get('comment');
	}

	protected function hydrateEnabledAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && boolval($attributes->get('enabled'));
	}

}
