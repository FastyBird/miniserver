<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
