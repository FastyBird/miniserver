<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Accounts;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use TypeError;
use ValueError;
use function in_array;
use function is_array;
use function is_scalar;
use function strval;

/**
 * Account entity hydrator trait
 *
 * @property-read Localization\Translator $translator
 */
trait HydratesAccount
{

	public function getEntityName(): string
	{
		return Entities\Accounts\Account::class;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateFirstNameAttribute(Objects\StandardObject $attributes): string
	{
		if (!$attributes->has('first_name') || !is_scalar($attributes->get('first_name'))) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/details/first_name',
				],
			);
		}

		return (string) $attributes->get('first_name');
	}

	/**
	 * @throws Exceptions\JsonApi
	 */
	protected function hydrateLastNameAttribute(Objects\StandardObject $attributes): string
	{
		if (!$attributes->has('last_name') || !is_scalar($attributes->get('last_name'))) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/details/last_name',
				],
			);
		}

		return (string) $attributes->get('last_name');
	}

	protected function hydrateMiddleNameAttribute(Objects\StandardObject $attributes): string|null
	{
		return $attributes->has('middle_name') && is_scalar(
			$attributes->get('middle_name'),
		) && (string) $attributes->get('middle_name') !== '' ? (string) $attributes->get('middle_name') : null;
	}

	/**
	 * Only validates. The base hydrator fills `details` from the nested object, through the
	 * `first_name`, `last_name` and `middle_name` mappings, so there is no value to return here.
	 *
	 * @throws Exceptions\JsonApiError
	 */
	protected function validateDetailsAttribute(
		Objects\StandardObject $attributes,
		Entities\Accounts\Account|null $entity = null,
	): void
	{
		$details = $attributes->get('details');

		if (!$details instanceof Objects\StandardObject) {
			// The base hydrator takes a JSON array for nested details. On create, reject it the way
			// it rejects any other non-object value. On update, it is ignored.
			if ($entity === null && is_array($details)) {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//api.hydrator.missingRequiredAttribute.heading')),
					strval($this->translator->translate('//api.hydrator.missingRequiredAttribute.message')),
					[
						'pointer' => '/data/attributes/details',
					],
				);
			}

			return;
		}

		foreach (['first_name', 'last_name'] as $name) {
			if (!$details->has($name)) {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/details/' . $name,
					],
				);
			}
		}
	}

	/**
	 * @return array{datetime: array{format: array<string, string>, week_start?: int, zone?: string}}
	 */
	protected function hydrateParamsAttribute(
		Objects\StandardObject $attributes,
	): array
	{
		$params = [
			'datetime' => [
				'format' => [],
			],
		];

		if ($attributes->has('week_start') && is_scalar($attributes->get('week_start'))) {
			$params['datetime']['week_start'] = (int) $attributes->get('week_start');
		}

		if (
			$attributes->has('datetime')
			&& $attributes->get('datetime') instanceof Objects\StandardObject
		) {
			$datetime = $attributes->get('datetime');

			if ($datetime->has('timezone') && is_scalar($datetime->get('timezone'))) {
				$params['datetime']['zone'] = (string) $datetime->get('timezone');
			}

			if ($datetime->has('date_format') && is_scalar($datetime->get('date_format'))) {
				$params['datetime']['format']['date'] = (string) $datetime->get('date_format');
			}

			if ($datetime->has('time_format') && is_scalar($datetime->get('time_format'))) {
				$params['datetime']['format']['time'] = (string) $datetime->get('time_format');
			}
		}

		return $params;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateStateAttribute(
		Objects\StandardObject $attributes,
	): Types\AccountState
	{
		if (
			!is_scalar($attributes->get('state'))
			|| Types\AccountState::tryFrom((string) $attributes->get('state')) === null
			|| !in_array(
				Types\AccountState::from((string) $attributes->get('state')),
				Types\AccountState::getAllowed(),
				true,
			)
		) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/state',
				],
			);
		}

		return Types\AccountState::from((string) $attributes->get('state'));
	}

}
