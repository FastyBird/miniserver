<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Exceptions;

use FastyBird\Core\Security\Exceptions;

final class AccountNotFound extends Exceptions\Authentication implements Exception
{

}
