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
use FastyBird\Library\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use FastyBird\Library\Phone;
use Nette\Localization;
use function is_scalar;
use function strval;

/**
 * SMS notification entity hydrator
 *
 * @extends Notification<Entities\Notifications\Sms>
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
		private readonly Phone\Phone $phone,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
	)
	{
		parent::__construct($managerRegistry, $translator);
	}

	public function getEntityName(): string
	{
		return Entities\Notifications\Sms::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws Phone\Exceptions\NoValidCountryException
	 * @throws Phone\Exceptions\NoValidPhoneException
	 * @throws Phone\Exceptions\NoValidTypeException
	 */
	protected function hydratePhoneAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Phone\Entities\Phone
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
