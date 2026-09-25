<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;

final class ForbiddenAccess extends RuntimeException implements Exceptions\Exception
{

}
