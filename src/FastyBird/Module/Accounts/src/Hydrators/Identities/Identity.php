<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Identities;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Override;
use function is_scalar;
use function strval;

/**
 * Identity entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Identities\Identity>
 */
final class Identity extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'uid',
		'password',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Identities\Identity::class;
	}

	/**
	 * @throws AccountsExceptions\InvalidState
	 * @throws ApiExceptions\JsonApiError
	 */
	protected function hydratePasswordAttribute(
		Objects\StandardObject $attributes,
	): Helpers\Password
	{
		if (!is_scalar($attributes->get('password'))) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/password',
				],
			);
		}

		return Helpers\Password::createFromString((string) $attributes->get('password'));
	}

}
