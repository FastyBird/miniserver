<?php declare(strict_types = 1);

/**
 * Email.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Schemas;

/**
 * Email entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Emails\Email>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Email extends Hydrators\Hydrator
{

	use TEmail;

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'address',
		1 => 'default',
		2 => 'verified',

		'private' => 'visibility',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
	];

}
