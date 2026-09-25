<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException as PHPRuntimeException;

final class BadResponse extends PHPRuntimeException implements Exceptions\Exception
{

}
