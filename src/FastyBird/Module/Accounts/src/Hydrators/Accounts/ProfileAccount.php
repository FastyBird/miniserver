<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Accounts;

use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;

/**
 * Profile account entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Accounts\Account>
 */
final class ProfileAccount extends Hydrators\Hydrator
{

	use TAccount;

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'details',

		// Not on the account entity: the base hydrator reuses this map for the nested `details`
		// object, where these keys fill Entities\Details\Details. They are not dead mappings.
		'first_name' => 'firstName',
		'last_name' => 'lastName',
		'middle_name' => 'middleName',
	];

	/** @var array<int|string, string> */
	protected array $compositedAttributes = [
		'params',
	];

}
