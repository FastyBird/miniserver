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

use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;

/**
 * Profile account entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Accounts\Account>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
