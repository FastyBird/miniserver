<?php declare(strict_types = 1);

/**
 * TAccount.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Accounts;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Nette\Utils;
use TypeError;
use ValueError;
use function assert;
use function in_array;
use function is_scalar;
use function strval;

/**
 * Account entity hydrator trait
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Localization\Translator $translator
 */
trait TAccount
{

	public function getEntityName(): string
	{
		return Entities\Accounts\Account::class;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateFirstNameAttribute(Objects\IStandardObject $attributes): string
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
	protected function hydrateLastNameAttribute(Objects\IStandardObject $attributes): string
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

	protected function hydrateMiddleNameAttribute(Objects\IStandardObject $attributes): string|null
	{
		return $attributes->has('middle_name') && is_scalar(
			$attributes->get('middle_name'),
		) && (string) $attributes->get('middle_name') !== '' ? (string) $attributes->get('middle_name') : null;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	protected function hydrateDetailsAttribute(
		Objects\IStandardObject $attributes,
	): Utils\ArrayHash|null
	{
		if (
			$attributes->has('details')
			&& $attributes->get('details') instanceof Objects\IStandardObject
		) {
			$details = $attributes->get('details');

			$update = new Utils\ArrayHash();
			$update['entity'] = Entities\Details\Details::class;

			if ($details->has('first_name')) {
				$update->offsetSet('firstName', $details->get('first_name'));

			} else {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/details/first_name',
					],
				);
			}

			if ($details->has('last_name')) {
				$update->offsetSet('lastName', $details->get('last_name'));

			} else {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.missingAttribute.message')),
					[
						'pointer' => '/data/attributes/details/last_name',
					],
				);
			}

			if ($details->has('middle_name') && $details->get('middle_name') !== '') {
				$update->offsetSet('middleName', $details->get('middle_name'));

			} else {
				$update->offsetSet('middleName', null);
			}

			return $update;
		}

		return null;
	}

	protected function hydrateParamsAttribute(
		Objects\IStandardObject $attributes,
	): Utils\ArrayHash
	{
		$params = Utils\ArrayHash::from([
			'datetime' => [
				'format' => [],
			],
		]);
		assert($params['datetime'] instanceof Utils\ArrayHash);

		if ($attributes->has('week_start') && is_scalar($attributes->get('week_start'))) {
			$params['datetime']->offsetSet('week_start', (int) $attributes->get('week_start'));
		}

		if (
			$attributes->has('datetime')
			&& $attributes->get('datetime') instanceof Objects\IStandardObject
		) {
			$datetime = $attributes->get('datetime');

			if ($datetime->has('timezone') && is_scalar($datetime->get('timezone'))) {
				$params['datetime']->offsetSet('zone', (string) $datetime->get('timezone'));
			}

			if ($datetime->has('date_format') && is_scalar($datetime->get('date_format'))) {
				assert($params['datetime']['format'] instanceof Utils\ArrayHash);
				$params['datetime']['format']->offsetSet('date', (string) $datetime->get('date_format'));
			}

			if ($datetime->has('time_format') && is_scalar($datetime->get('time_format'))) {
				assert($params['datetime']['format'] instanceof Utils\ArrayHash);
				$params['datetime']['format']->offsetSet('time', (string) $datetime->get('time_format'));
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
		Objects\IStandardObject $attributes,
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
