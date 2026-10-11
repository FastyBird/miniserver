<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Hydrators\Actions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Ramsey\Uuid;
use function is_scalar;
use function strval;

/**
 * Channel property action entity hydrator
 *
 * @extends PropertyAction<Entities\Actions\ChannelPropertyAction>
 */
final class ChannelPropertyAction extends PropertyAction
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'channel',
		'property',
		'value',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Actions\ChannelPropertyAction::class;
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
