<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Ui\Entities;
use Fig\Http\Message\StatusCodeInterface;
use Override;
use function is_int;
use function is_scalar;
use function strval;

/**
 * Chart graph widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\ChartGraph>
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
		'period' => 'period',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\ChartGraph::class;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateEnableMinMaxAttribute(Objects\StandardObject $attributes): bool
	{
		if (
			!is_scalar($attributes->get('enable_min_max'))
			|| (string) $attributes->get('enable_min_max') === ''
		) {
			throw new Exceptions\JsonApiError(
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

	/**
	 * The period of history the chart shows, in seconds: a whole number the history can be asked for, or null for
	 * the default
	 *
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydratePeriodAttribute(Objects\StandardObject $attributes): int|null
	{
		$value = $attributes->get('period');

		if ($value === null) {
			return null;
		}

		if (
			!is_int($value)
			|| $value < Entities\Widgets\Displays\ChartGraph::MIN_PERIOD
			|| $value > Entities\Widgets\Displays\ChartGraph::MAX_PERIOD
		) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/period',
				],
			);
		}

		return $value;
	}

}
