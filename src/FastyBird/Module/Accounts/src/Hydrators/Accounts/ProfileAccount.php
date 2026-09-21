<?php declare(strict_types = 1);

/**
 * ProfileAccount.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           19.08.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Accounts;

use FastyBird\Core\Persistence\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Schemas;

/**
 * Profile account entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Accounts\Account>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ProfileAccount extends JsonApiHydrators\Hydrator
{

	use TAccount;

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'details',

		'first_name' => 'firstName',
		'last_name' => 'lastName',
		'middle_name' => 'middleName',
	];

	/** @var array<int|string, string> */
	protected array $compositedAttributes = [
		'params',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Accounts\Account::RELATIONSHIPS_ROLES,
	];

}
