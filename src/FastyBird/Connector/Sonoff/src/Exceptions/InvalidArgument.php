<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
