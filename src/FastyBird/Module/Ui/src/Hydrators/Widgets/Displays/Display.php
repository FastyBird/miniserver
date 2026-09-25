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

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use function is_scalar;
use function strval;

/**
 * Widget display entity hydrator
 *
 * @template  T of Entities\Widgets\Displays\Display
 * @extends   Hydrators\Hydrator<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Display extends Hydrators\Hydrator
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
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydratePrecisionAttribute(Objects\IStandardObject $attributes): int
	{
		if (
			!is_scalar($attributes->get('precision'))
			|| (string) $attributes->get('precision') === ''
		) {
			throw new Exceptions\JsonApiError(
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
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateMinimumValueAttribute(Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('minimum_value'))
			|| (string) $attributes->get('minimum_value') === ''
		) {
			throw new Exceptions\JsonApiError(
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
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateMaximumValueAttribute(Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('maximum_value'))
			|| (string) $attributes->get('maximum_value') === ''
		) {
			throw new Exceptions\JsonApiError(
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
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateStepValueAttribute(Objects\IStandardObject $attributes): float
	{
		if (
			!is_scalar($attributes->get('step_value'))
			|| (string) $attributes->get('step_value') === ''
		) {
			throw new Exceptions\JsonApiError(
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
