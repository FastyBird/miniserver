<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

final class NotSupported extends PHPInvalidArgumentException implements Exception
{

}
