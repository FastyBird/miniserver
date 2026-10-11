<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class NotReachable extends PHPInvalidArgumentException implements Exception
{

}
