<?php declare(strict_types = 1);

/**
 * PropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Hydrators\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Hydrators as TriggersHydrators;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function is_bool;
use function is_scalar;
use function strtolower;
use function strval;

/**
 * Property condition entity hydrator
 *
 * @template T of Entities\Conditions\PropertyCondition
 * @extends  TriggersHydrators\Conditions\Condition<T>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class PropertyCondition extends TriggersHydrators\Conditions\Condition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateDeviceAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Uuid\UuidInterface
	{
		if (
			!is_scalar($attributes->get('device'))
			|| !$attributes->has('device')
			|| $attributes->get('device') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('device'))
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/device',
				],
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('device'));
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydratePropertyAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Uuid\UuidInterface
	{
		if (
			!is_scalar($attributes->get('property'))
			|| !$attributes->has('property')
			|| $attributes->get('property') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('property'))
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/property',
				],
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('property'));
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateOperatorAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): TriggersTypes\ConditionOperator
	{
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('operator'))
			|| !$attributes->has('operator')
			|| $attributes->get('operator') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/operator',
				],
			);

			// ...and have to be valid value
		} elseif (TriggersTypes\ConditionOperator::tryFrom(strval($attributes->get('operator'))) === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidOperator.heading')),
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidOperator.message')),
				[
					'pointer' => '/data/attributes/operator',
				],
			);
		}

		return TriggersTypes\ConditionOperator::from(strval($attributes->get('operator')));
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateOperandAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): string
	{
		if (
			!is_scalar($attributes->get('operand'))
			|| !$attributes->has('operand')
			|| $attributes->get('operand') === ''
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/operand',
				],
			);
		}

		$operand = $attributes->get('operand');

		return is_bool($operand) ? ($operand ? 'true' : 'false') : strtolower((string) $operand);
	}

}
