<?php declare(strict_types = 1);

/**
 * DataCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Automator\DateTime\Hydrators\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Module\Triggers\Hydrators as TriggersHydrators;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use function is_scalar;
use function strval;

/**
 * Time condition entity hydrator
 *
 * @extends TriggersHydrators\Conditions\Condition<Entities\Conditions\DateCondition>
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateDateAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): DateTimeInterface
	{
		// Condition date have to be set
		if (!is_scalar($attributes->get('date')) || !$attributes->has('date')) {
			throw new JsonApiExceptions\JsonApiError(
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
			throw new JsonApiExceptions\JsonApiError(
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
