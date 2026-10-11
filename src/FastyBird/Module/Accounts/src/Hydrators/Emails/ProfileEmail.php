<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Core\Api\Hydrators;
use FastyBird\Module\Accounts\Entities;

/**
 * Profile email entity hydrator
 *
 * @extends Hydrators\Hydrator<Entities\Emails\Email>
 */
final class ProfileEmail extends Hydrators\Hydrator
{

	use HydratesEmail;

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'address',

		'default' => 'default',
		'private' => 'visibility',
	];

}
