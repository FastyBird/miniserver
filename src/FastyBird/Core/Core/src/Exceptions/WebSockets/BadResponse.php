<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions\WebSockets;

use FastyBird\Core\Exceptions\Exception;
use RuntimeException as PHPRuntimeException;

class BadResponse extends PHPRuntimeException implements Exception
{

}
