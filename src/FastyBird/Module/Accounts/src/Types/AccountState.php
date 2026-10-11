<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Types;

/**
 * Account state type
 */
enum AccountState: string
{

	case ACTIVE = 'active';

	case BLOCKED = 'blocked';

	case DELETED = 'deleted';

	case NOT_ACTIVATED = 'not_activated';

	case APPROVAL_WAITING = 'approval_waiting';

	/**
	 * @return array<self>
	 */
	public static function getAllowed(): array
	{
		return [
			self::ACTIVE,
			self::BLOCKED,
			self::DELETED,
		];
	}

}
