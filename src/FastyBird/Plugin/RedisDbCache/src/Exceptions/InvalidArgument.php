<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
