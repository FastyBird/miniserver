<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

final class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
