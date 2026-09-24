<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.06.19
 */

namespace FastyBird\Module\Devices\Hydrators\Devices;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;
use function is_scalar;

/**
 * Device entity hydrator
 *
 * @template  T of Entities\Devices\Device
 * @extends   Hydrators\Hydrator<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes
		= [
			'category',
			'identifier',
			'name',
			'comment',
			'params',
		];

	/** @var array<string> */
	protected array $relationships
		= [
			Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR,
			Schemas\Devices\Device::RELATIONSHIPS_PARENTS,
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

}
