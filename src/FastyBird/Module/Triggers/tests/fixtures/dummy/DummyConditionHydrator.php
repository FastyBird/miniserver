<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Types;
use Fig\Http\Message\StatusCodeInterface;
use Ramsey\Uuid;
use function is_bool;
use function is_scalar;
use function strtolower;
use function strval;

final class DummyConditionHydrator extends Hydrators\Conditions\Condition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'operator',
		1 => 'operand',
		2 => 'enabled',
		'watch_item' => 'watchItem',
	];

	public function getEntityName(): string
	{
		return DummyConditionEntity::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateWatchItemAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): Uuid\UuidInterface
	{
		if (
			!is_scalar($attributes->get('watch_item'))
			|| !$attributes->has('watch_item')
			|| $attributes->get('watch_item') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('watch_item'))
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/watch_item',
				],
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('watch_item'));
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateOperatorAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): Types\ConditionOperator
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
		} elseif (Types\ConditionOperator::tryFrom($attributes->get('operator')) === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidOperator.heading')),
				strval($this->translator->translate('//triggers-module.conditions.messages.invalidOperator.message')),
				[
					'pointer' => '/data/attributes/operator',
				],
			);
		}

		return Types\ConditionOperator::from($attributes->get('operator'));
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateOperandAttribute(
		JsonApi\Objects\IStandardObject $attributes,
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
