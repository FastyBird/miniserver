<?php declare(strict_types = 1);

/**
 * ChartGraph.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Module\Ui\Entities;
use Fig\Http\Message\StatusCodeInterface;
use function is_scalar;
use function strval;

/**
 * Chart graph widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\ChartGraph>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChartGraph extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'minimum_value' => 'minimumValue',
		'maximum_value' => 'maximumValue',
		'precision' => 'precision',
		'step_value' => 'stepValue',
		'enable_min_max' => 'enableMinMax',
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\ChartGraph::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateEnableMinMaxAttribute(JsonApi\Objects\IStandardObject $attributes): bool
	{
		if (
			!is_scalar($attributes->get('enable_min_max'))
			|| (string) $attributes->get('enable_min_max') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/enable_min_max',
				],
			);
		}

		return (bool) $attributes->get('enable_min_max');
	}

}
