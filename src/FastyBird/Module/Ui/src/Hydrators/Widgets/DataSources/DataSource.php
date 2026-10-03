<?php declare(strict_types = 1);

/**
 * DataSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           27.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\DataSources;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use function is_array;
use function strval;

/**
 * Data source entity hydrator
 *
 * @template  T of Entities\Widgets\DataSources\DataSource
 * @extends   Hydrators\Hydrator<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class DataSource extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'params',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\DataSources\DataSource::RELATIONSHIPS_WIDGET,
	];

	/**
	 * The generic mapping resolves `params` to a mixed field: TEntityParams types the property
	 * `array|null` but getParams() returns Utils\ArrayHash. A mixed field hands the raw resource
	 * object to setParams(array), which rejects it, so the object is converted here.
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateParamsAttribute(Objects\IStandardObject $attributes): array
	{
		$params = $attributes->get('params');

		if ($params === null) {
			return [];
		}

		if ($params instanceof Objects\IStandardObject) {
			return $params->toArray();
		}

		if (is_array($params)) {
			return $params;
		}

		throw new Exceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.heading')),
			strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.message')),
			[
				'pointer' => '/data/attributes/params',
			],
		);
	}

}
