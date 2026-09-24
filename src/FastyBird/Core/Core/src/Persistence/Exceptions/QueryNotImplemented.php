<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;

final class QueryNotImplemented extends RuntimeException implements Exceptions\Exception
{

}
