<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Exceptions;

use FastyBird\Module\Ui\Exceptions\Exception;
use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
