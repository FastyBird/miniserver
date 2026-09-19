<?php declare(strict_types = 1);

/**
 * Display.php
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

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use function is_scalar;
use function strval;

/**
 * Widget display entity hydrator
 *
 * @template  T of Entities\Widgets\Displays\Display
 * @extends   JsonApiHydrators\Hydrator<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Display extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'params',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\Display\Display::RELATIONSHIPS_WIDGET,
	];

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydratePrecisionAttribute(JsonApi\Objects\IStandardObject $attributes): int
	{
		if (
			!is_scalar($attributes->get('precision'))
			|| (string) $attributes->get('precision') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/precision',
				],
			);
		}

		return (int) $attributes->get('precision');
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateMinimumValueAttribute(JsonApi\Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('minimum_value'))
			|| (string) $attributes->get('minimum_value') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/minimum_value',
				],
			);
		}

		return (float) $attributes->get('minimum_value');
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateMaximumValueAttribute(JsonApi\Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('maximum_value'))
			|| (string) $attributes->get('maximum_value') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/maximum_value',
				],
			);
		}

		return (float) $attributes->get('maximum_value');
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateStepValueAttribute(JsonApi\Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('step_value'))
			|| (string) $attributes->get('step_value') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/step_value',
				],
			);
		}

		return (float) $attributes->get('step_value');
	}

}
