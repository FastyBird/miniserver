<?php declare(strict_types = 1);

/**
 * ProfileEmail.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           21.08.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Library\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Accounts\Entities;

/**
 * Profile email entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Emails\Email>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ProfileEmail extends JsonApiHydrators\Hydrator
{

	use TEmail;

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'address',

		'default' => 'default',
		'private' => 'visibility',
	];

}
