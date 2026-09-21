<?php declare(strict_types = 1);

/**
 * TimeCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DateTime\Hydrators\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Hydrators as TriggersHydrators;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use function in_array;
use function is_array;
use function is_scalar;
use function strval;

/**
 * Time condition entity hydrator
 *
 * @extends TriggersHydrators\Conditions\Condition<Entities\Conditions\TimeCondition>
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TimeCondition extends TriggersHydrators\Conditions\Condition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'time',
		'days',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Conditions\TimeCondition::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateTimeAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): DateTimeInterface
	{
		// Condition time have to be set
		if (!is_scalar($attributes->get('time')) || !$attributes->has('time')) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/time',
				],
			);
		}

		$date = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, (string) $attributes->get('time'));

		if (
			!$date instanceof DateTimeInterface
			|| $date->format(DateTimeInterface::ATOM) !== $attributes->get('time')
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidTime.heading')),
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidTime.message')),
				[
					'pointer' => '/data/attributes/time',
				],
			);
		}

		return $date;
	}

	/**
	 * @return array<int>
	 *
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateDaysAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): array
	{
		// Condition days have to be set
		if (!$attributes->has('days')) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/days',
				],
			);
		} elseif (!is_array($attributes->get('days'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidDays.heading')),
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidDays.message')),
				[
					'pointer' => '/data/attributes/days',
				],
			);
		}

		$days = [];

		foreach ($attributes->get('days') as $day) {
			if (in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				$days[] = $day;
			}
		}

		return $days;
	}

}
