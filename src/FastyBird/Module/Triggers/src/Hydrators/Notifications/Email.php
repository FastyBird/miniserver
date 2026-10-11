<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Notifications;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use function is_scalar;
use function strval;

/**
 * Email notification entity hydrator
 *
 * @extends Notification<Entities\Notifications\Email>
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
	 * @throws Exceptions\JsonApi
	 */
	protected function hydrateEmailAttribute(
		Objects\StandardObject $attributes,
	): string
	{
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('email'))
			|| !$attributes->has('email')
			|| !Utils\Validators::isEmail((string) $attributes->get('email'))
		) {
			throw new Exceptions\JsonApiError(
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
