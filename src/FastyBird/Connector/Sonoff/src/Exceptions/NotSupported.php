<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class NotSupported extends PHPInvalidArgumentException implements Exception
{

}
