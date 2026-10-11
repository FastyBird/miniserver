<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Hydrators\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Triggers\Hydrators as TriggersHydrators;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use function is_scalar;
use function strval;

/**
 * Time condition entity hydrator
 *
 * @extends TriggersHydrators\Conditions\Condition<Entities\Conditions\DateCondition>
 */
final class DataCondition extends TriggersHydrators\Conditions\Condition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'date',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Conditions\DateCondition::class;
	}

	/**
	 * @throws Exceptions\JsonApi
	 */
	protected function hydrateDateAttribute(
		Objects\StandardObject $attributes,
	): DateTimeInterface
	{
		// Condition date have to be set
		if (!is_scalar($attributes->get('date')) || !$attributes->has('date')) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/date',
				],
			);
		}

		$date = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, (string) $attributes->get('date'));

		if (
			!$date instanceof DateTimeInterface
			|| $date->format(DateTimeInterface::ATOM) !== $attributes->get('date')
		) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidTime.heading')),
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidTime.message')),
				[
					'pointer' => '/data/attributes/date',
				],
			);
		}

		return $date;
	}

}
