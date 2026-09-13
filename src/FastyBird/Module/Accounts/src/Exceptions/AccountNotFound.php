<?php declare(strict_types = 1);

/**
 * AccountNotFound.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Exceptions;

use FastyBird\Core\SimpleAuth\Exceptions as SimpleAuthExceptions;

class AccountNotFound extends SimpleAuthExceptions\Authentication implements Exception
{

}
