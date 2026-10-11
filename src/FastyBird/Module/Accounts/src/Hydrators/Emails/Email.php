<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Schemas;

/**
 * Email entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Emails\Email>
 */
final class Email extends Hydrators\Hydrator
{

	use HydratesEmail;

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
