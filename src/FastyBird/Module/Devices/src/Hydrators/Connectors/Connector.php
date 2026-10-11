<?php declare(strict_types = 1);

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

	protected function hydrateNameAttribute(Objects\StandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
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

	protected function hydrateEnabledAttribute(Objects\StandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && boolval($attributes->get('enabled'));
	}

}
