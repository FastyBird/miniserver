<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;

final class UnauthorizedAccess extends RuntimeException implements Exceptions\Exception
{

}
