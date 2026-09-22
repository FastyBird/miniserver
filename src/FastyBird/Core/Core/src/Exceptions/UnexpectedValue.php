<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions;

use UnexpectedValueException as PHPUnexpectedValueException;

class UnexpectedValue extends PHPUnexpectedValueException implements Exception
{

}
