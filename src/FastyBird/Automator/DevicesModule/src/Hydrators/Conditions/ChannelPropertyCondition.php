<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Hydrators\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Override;
use Ramsey\Uuid;
use function is_scalar;
use function strval;

/**
 * Channel property condition entity hydrator
 *
 * @extends PropertyCondition<Entities\Conditions\ChannelPropertyCondition>
 */
final class ChannelPropertyCondition extends PropertyCondition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'channel',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Conditions\ChannelPropertyCondition::class;
	}

	/**
	 * @throws Exceptions\JsonApi
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function hydrateChannelAttribute(
		Objects\StandardObject $attributes,
	): Uuid\UuidInterface
	{
		if (
			!is_scalar($attributes->get('channel'))
			|| !$attributes->has('channel')
			|| $attributes->get('channel') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('channel'))
		) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/channel',
				],
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('channel'));
	}

}
