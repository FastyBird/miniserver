<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           15.08.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Identities;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use function is_scalar;
use function strval;

/**
 * Identity entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Identities\Identity>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Identity extends Hydrators\Hydrator
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

	public function getEntityName(): string
	{
		return Entities\Identities\Identity::class;
	}

	/**
	 * @throws AccountsExceptions\InvalidState
	 * @throws ApiExceptions\JsonApiError
	 */
	protected function hydratePasswordAttribute(
		Objects\IStandardObject $attributes,
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
