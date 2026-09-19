<?php declare(strict_types = 1);

/**
 * Email.php
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

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use function is_scalar;
use function strval;

/**
 * Email notification entity hydrator
 *
 * @extends Notification<Entities\Notifications\Email>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Email extends Notification
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'email',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Notifications\Email::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateEmailAttribute(
		JsonApi\Objects\IStandardObject $attributes,
	): string
	{
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('email'))
			|| !$attributes->has('email')
			|| !Utils\Validators::isEmail((string) $attributes->get('email'))
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval(
					$this->translator->translate(
						'//triggers-module.notifications.messages.invalidEmailAddress.heading',
					),
				),
				strval(
					$this->translator->translate(
						'//triggers-module.notifications.messages.invalidEmailAddress.message',
					),
				),
				[
					'pointer' => '/data/attributes/email',
				],
			);
		}

		return (string) $attributes->get('email');
	}

}
