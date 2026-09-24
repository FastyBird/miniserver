<?php declare(strict_types = 1);

/**
 * Sms.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Notifications;

use Doctrine\Persistence;
use Error;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Phone\Entities as PhoneEntities;
use FastyBird\Core\Phone\Exceptions as PhoneExceptions;
use FastyBird\Core\Phone\Services;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use function is_scalar;
use function strval;

/**
 * SMS notification entity hydrator
 *
 * @extends Notification<TriggersEntities\Notifications\Sms>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sms extends Notification
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'phone',
		'enabled',
	];

	public function __construct(
		private readonly Services\PhoneNumberHelper $phone,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
	)
	{
		parent::__construct($managerRegistry, $translator);
	}

	public function getEntityName(): string
	{
		return TriggersEntities\Notifications\Sms::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws PhoneExceptions\NoValidCountry
	 * @throws PhoneExceptions\NoValidPhone
	 * @throws PhoneExceptions\NoValidType
	 * @throws Error
	 */
	protected function hydratePhoneAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): PhoneEntities\Phone
	{
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('phone'))
			|| !$attributes->has('phone')
			|| !$this->phone->isValid((string) $attributes->get('phone'), 'CZ')
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//triggers-module.notifications.messages.invalidPhone.heading')),
				strval($this->translator->translate('//triggers-module.notifications.messages.invalidPhone.message')),
				[
					'pointer' => '/data/attributes/phone',
				],
			);
		}

		return $this->phone->parse((string) $attributes->get('phone'), 'CZ');
	}

}
